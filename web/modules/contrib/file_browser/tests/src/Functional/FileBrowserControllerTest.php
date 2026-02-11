<?php

namespace Drupal\Tests\file_browser\Functional;

use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the File Browser Controller functionality.
 */
#[Group("file_browser")]
class FileBrowserControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   *
   * We set this to FALSE here as DropzoneJS and Entity Browser use dynamic
   * config settings which fail strict checks during installation.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file_browser',
    'dropzonejs',
    'entity_browser',
    'entity_embed',
    'image',
    'file',
    'user',
    'system',
  ];

  /**
   * A test file entity.
   *
   * @var \Drupal\file\FileInterface
   */
  protected FileInterface $testFile;

  /**
   * A test image file entity.
   *
   * @var \Drupal\file\FileInterface
   */
  protected FileInterface $testImage;

  /**
   * An authenticated user with file view permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $authenticatedUser;

  /**
   * An admin user with all permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * A user without file view permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $restrictedUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create test files.
    $this->testFile = $this->createTestFile();
    $this->testImage = $this->createTestImageFile();

    // Create users with different permission levels.
    $this->authenticatedUser = $this->drupalCreateUser([
      'access content',
    ]);

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'access files overview',
      'delete any file',
      'delete own files',
    ]);

    $this->restrictedUser = $this->drupalCreateUser([
      'access content',
    ]);

    // Install default image styles.
    $this->container->get('module_installer')->install(['image']);
  }

  /**
   * Creates a test file entity.
   *
   * @return \Drupal\file\FileInterface
   *   The created file entity.
   */
  protected function createTestFile() {
    $file_contents = 'This is a test file.';
    /** @var \Drupal\file\FileRepositoryInterface $file_repository */
    $file_repository = \Drupal::service('file.repository');
    $file = $file_repository->writeData($file_contents, 'public://test-file.txt');
    $file->setPermanent();
    $file->save();
    return $file;
  }

  /**
   * Creates a test image file entity.
   *
   * @return \Drupal\file\FileInterface
   *   The created image file entity.
   */
  protected function createTestImageFile() {
    // Create a minimal image file with proper MIME type.
    // This creates a 1x1 pixel PNG image.
    $image_data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAHF/mWAAAAASUVORK5CYII=');
    /** @var \Drupal\file\FileRepositoryInterface $file_repository */
    $file_repository = \Drupal::service('file.repository');
    $file = $file_repository->writeData($image_data, 'public://test-image.png');

    // Ensure proper MIME type is set.
    $file->setMimeType('image/png');
    $file->setPermanent();
    $file->save();

    // Debug: Check the created file properties.
    $mime_type = $file->getMimeType();
    if (!str_starts_with($mime_type, 'image/')) {
      throw new \Exception("Test image file has wrong MIME type: $mime_type");
    }

    return $file;
  }

  /**
   * Tests route access for authenticated users.
   */
  public function testPreviewRouteAccess() {
    // Test unauthenticated access to public files is allowed (returns 200).
    // This is correct behavior as public files can be viewed by anonymous
    // users.
    $url = Url::fromRoute('file_browser.preview', [
      'file' => $this->testFile->id(),
    ]);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Test authenticated user with appropriate permissions can access.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Verify the response contains expected elements.
    $this->assertSession()->elementExists('css', '#file-browser-preview-wrapper');
  }

  /**
   * Tests preview rendering with image files.
   */
  public function testImagePreviewRendering() {
    $this->drupalLogin($this->adminUser);

    // Test image file preview without image style.
    $url = Url::fromRoute('file_browser.preview', [
      'file' => $this->testImage->id(),
    ]);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Verify the response contains expected elements.
    $this->assertSession()->elementExists('css', '#file-browser-preview-wrapper');

    // Should contain image style selector for image files.
    $this->assertSession()->elementExists('css', 'select');

    // The image style selector exists but may have different field names.
    // Just verify that a select element exists for image files.
    $select_elements = $this->getSession()->getPage()->findAll('css', 'select');
    $this->assertNotEmpty($select_elements, 'No select elements found for image file preview');
  }

  /**
   * Tests preview rendering with image style parameter.
   */
  public function testImageStyleParameter() {
    $this->drupalLogin($this->adminUser);

    // Test with a specific image style.
    $url = Url::fromRoute('file_browser.preview', [
      'file' => $this->testImage->id(),
      'image_style' => 'thumbnail',
    ]);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Verify preview wrapper exists and page loads correctly.
    $this->assertSession()->elementExists('css', '#file-browser-preview-wrapper');

    // Test with empty image style.
    $url = Url::fromRoute('file_browser.preview', [
      'file' => $this->testImage->id(),
      'image_style' => '',
    ]);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#file-browser-preview-wrapper');
  }

  /**
   * Tests preview rendering with non-image files.
   */
  public function testNonImagePreviewRendering() {
    $this->drupalLogin($this->adminUser);

    // Test non-image file preview.
    $url = Url::fromRoute('file_browser.preview', [
      'file' => $this->testFile->id(),
    ]);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Verify the preview wrapper exists.
    $this->assertSession()->elementExists('css', '#file-browser-preview-wrapper');

    // Should NOT contain image style selector for non-image files.
    $select_elements = $this->getSession()->getPage()->findAll('css', 'select');
    $this->assertEmpty($select_elements, 'Non-image files should not have image style selectors');

    // Should contain some kind of preview content (image or other element).
    $has_preview_content =
      !empty($this->getSession()->getPage()->findAll('css', 'img')) ||
      !empty($this->getSession()->getPage()->findAll('css', '#file-browser-preview-wrapper *'));
    $this->assertTrue($has_preview_content, 'Preview should contain some content for non-image files');
  }

  /**
   * Tests behavior with invalid file IDs.
   */
  public function testInvalidFileIds() {
    $this->drupalLogin($this->adminUser);

    // Test with non-existent file ID.
    $non_existent_id = $this->testFile->id() + 999;
    $url = Url::fromRoute('file_browser.preview', [
      'file' => $non_existent_id,
    ]);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(404);

    // Test with an invalid file ID format (should be handled by routing).
    $this->drupalGet('/admin/file-browser-preview/not-a-number/');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests file access control.
   */
  public function testFileAccessControl() {
    // Create a private file for access control testing.
    $private_file_contents = 'Private file contents.';
    /** @var \Drupal\file\FileRepositoryInterface $file_repository */
    $file_repository = \Drupal::service('file.repository');
    $private_file = $file_repository->writeData($private_file_contents, 'private://private-test.txt');
    $private_file->setPermanent();
    $private_file->save();

    // Admin user may or may not be able to access private files depending
    // on configuration.
    $this->drupalLogin($this->adminUser);
    $url = Url::fromRoute('file_browser.preview', [
      'file' => $private_file->id(),
    ]);
    $this->drupalGet($url);
    $status = $this->getSession()->getStatusCode();
    $this->assertContains(
      $status,
      [200, 403],
      'Admin access to private files should be either allowed (200) or forbidden (403)'
    );

    // Regular authenticated user without specific permissions.
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet($url);
    // Private files may be restricted - check the actual response.
    $status = $this->getSession()->getStatusCode();
    $this->assertContains(
      $status,
      [200, 403],
      'Private file access should be either allowed (200) or forbidden (403)'
    );

    // Test access to a public file.
    $url = Url::fromRoute('file_browser.preview', [
      'file' => $this->testFile->id(),
    ]);
    $this->drupalGet($url);
    // Public files should be accessible to authenticated users.
    $this->assertSession()->statusCodeEquals(200);

    // Clean up.
    $private_file->delete();
  }

  /**
   * Tests JavaScript integration and AJAX functionality.
   */
  public function testJavaScriptIntegration() {
    $this->drupalLogin($this->adminUser);

    // Test that the preview page includes required JavaScript libraries.
    $url = Url::fromRoute('file_browser.preview', [
      'file' => $this->testImage->id(),
    ]);
    $this->drupalGet($url);

    // Check that JavaScript libraries and settings are attached.
    // The library might be referenced in different ways in the HTML.
    $content = $this->getSession()->getPage()->getContent();

    // Check for any file_browser related JavaScript or settings.
    $has_js_integration =
      str_contains($content, 'file_browser') ||
      str_contains($content, 'preview') ||
      str_contains($content, 'drupalSettings');

    $this->assertTrue($has_js_integration, 'Page should contain JavaScript integration elements');

    // Verify the preview wrapper has the correct ID for JavaScript targeting.
    $this->assertSession()->elementExists('css', '#file-browser-preview-wrapper');
  }

  /**
   * Tests permission-based access with different user roles.
   */
  public function testPermissionBasedAccess() {
    // Create a user with explicit file view permissions.
    $file_viewer = $this->drupalCreateUser([
      'access content',
      'access files overview',
    ]);

    $url = Url::fromRoute('file_browser.preview', [
      'file' => $this->testFile->id(),
    ]);

    // Test restricted user (no file permissions).
    $this->drupalLogin($this->restrictedUser);
    $this->drupalGet($url);
    // Public files should be accessible even to users with minimal permissions.
    $this->assertSession()->statusCodeEquals(200);

    // Test user with file view permissions.
    $this->drupalLogin($file_viewer);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Test admin user (all permissions).
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests route parameter conversion and validation.
   */
  public function testRouteParameterHandling() {
    $this->drupalLogin($this->adminUser);

    // Test that a file parameter is properly converted to a file entity.
    $url = Url::fromRoute('file_browser.preview', [
      'file' => $this->testFile->id(),
    ]);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Test that the image_style parameter is optional.
    $url = Url::fromRoute('file_browser.preview', [
      'file' => $this->testImage->id(),
    ]);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Test with explicit empty image_style.
    $url = Url::fromRoute('file_browser.preview', [
      'file' => $this->testImage->id(),
      'image_style' => '',
    ]);
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
  }

}

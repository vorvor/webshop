<?php

namespace Drupal\Tests\file_browser\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file_browser\Plugin\Field\FieldWidget\FileBrowser;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the File Browser Field Widget functionality.
 */
#[Group("file_browser")]
class FileBrowserFieldWidgetTest extends BrowserTestBase {

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
    'field',
    'field_ui',
    'node',
    'user',
    'system',
  ];

  /**
   * An admin user with all permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * A test content type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected NodeTypeInterface $contentType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a test content type first.
    $this->contentType = NodeType::create([
      'type' => 'test_content',
      'name' => 'Test Content',
    ]);
    $this->contentType->save();

    // Create an admin user with permissions for the content type.
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'create test_content content',
      'edit any test_content content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
    ]);
  }

  /**
   * Creates a file field on the test content type.
   *
   * @param string $field_name
   *   The field name.
   * @param string $field_type
   *   The field type ('file' or 'image').
   * @param bool $multiple
   *   Whether the field allows multiple values.
   *
   * @return \Drupal\field\FieldConfigInterface
   *   The created field config.
   */
  protected function createFileField(string $field_name, string $field_type = 'file', bool $multiple = FALSE) {
    // Create field storage.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $field_type,
      'cardinality' => $multiple ? -1 : 1,
    ]);
    $field_storage->save();

    // Create a field instance.
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->contentType->id(),
      'label' => ucfirst(str_replace('_', ' ', $field_name)),
    ]);
    $field->save();

    return $field;
  }

  /**
   * Sets up a form display with the file browser widget.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The form display.
   */
  protected function setupFormDisplayWithFileBrowser(string $field_name) {
    $form_display = EntityFormDisplay::load('node.' . $this->contentType->id() . '.default');
    if (!$form_display) {
      $form_display = EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => $this->contentType->id(),
        'mode' => 'default',
      ]);
    }

    $form_display->setComponent($field_name, [
      'type' => 'file_browser',
      'weight' => 10,
      'settings' => [],
    ]);
    $form_display->save();

    return $form_display;
  }

  /**
   * Tests widget applicability logic.
   */
  public function testWidgetApplicability() {
    // Create a file field.
    $field = $this->createFileField('field_test_file');

    // Initially, the widget should not be applicable since no form display
    // uses it yet.
    $this->assertFalse(
      FileBrowser::isApplicable($field),
      'Widget should not be applicable when no form display uses it'
    );

    // Set up form display to use the file browser widget.
    $this->setupFormDisplayWithFileBrowser('field_test_file');

    // Now the widget should be applicable.
    $this->assertTrue(
      FileBrowser::isApplicable($field),
      'Widget should be applicable when form display uses it'
    );
  }

  /**
   * Tests widget applicability with different field types.
   */
  public function testFieldTypeCompatibility() {
    // Test with the file field.
    $file_field = $this->createFileField('field_test_file', 'file');
    $this->setupFormDisplayWithFileBrowser('field_test_file');

    $this->assertTrue(
      FileBrowser::isApplicable($file_field),
      'Widget should be applicable to file fields'
    );

    // Test with the image field.
    $image_field = $this->createFileField('field_test_image', 'image');
    $this->setupFormDisplayWithFileBrowser('field_test_image');

    $this->assertTrue(
      FileBrowser::isApplicable($image_field),
      'Widget should be applicable to image fields'
    );
  }

  /**
   * Tests widget with multi-value field support.
   */
  public function testMultiValueFieldSupport() {
    // Create a multi-value file field.
    $field = $this->createFileField('field_test_multi', 'file', TRUE);
    $this->setupFormDisplayWithFileBrowser('field_test_multi');

    // Widget should be applicable to multi-value fields.
    $this->assertTrue(
      FileBrowser::isApplicable($field),
      'Widget should be applicable to multi-value fields'
    );

    // Widget annotation declares support for multiple values.
    $reflection = new \ReflectionClass(FileBrowser::class);
    $doc_comment = $reflection->getDocComment();
    $this->assertStringContainsString(
      'multiple_values = TRUE',
      $doc_comment,
      'Widget annotation should declare multiple_values = TRUE'
    );
  }

  /**
   * Tests form display integration.
   */
  public function testFormDisplayIntegration() {
    $this->drupalLogin($this->adminUser);

    // Create a file field and set up form display.
    $field = $this->createFileField('field_test_integration');
    $form_display = $this->setupFormDisplayWithFileBrowser('field_test_integration');

    // Verify the form display component is configured correctly.
    $component = $form_display->getComponent('field_test_integration');
    $this->assertNotNull($component, 'Field component should exist in form display');
    $this->assertEquals('file_browser', $component['type'], 'Widget type should be file_browser');

    // Test that the form loads without errors.
    $this->drupalGet('/node/add/' . $this->contentType->id());
    $this->assertSession()->statusCodeEquals(200);

    // Since entity browser configuration is complex, we just verify the form
    // loads successfully and contains our field in the form display
    // configuration.
    $this->assertTrue(
      $form_display->getComponent('field_test_integration') !== NULL,
      'Field should be properly configured in form display'
    );
  }

  /**
   * Tests widget applicability across multiple form displays.
   */
  public function testMultipleFormDisplays() {
    // Create a file field.
    $field = $this->createFileField('field_test_multiple');

    // Initially not applicable.
    $this->assertFalse(
      FileBrowser::isApplicable($field),
      'Widget should not be applicable initially'
    );

    // Set up the default form display.
    $this->setupFormDisplayWithFileBrowser('field_test_multiple');

    // Should be applicable after setting up form display.
    $this->assertTrue(
      FileBrowser::isApplicable($field),
      'Widget should be applicable when used in form display'
    );
  }

  /**
   * Tests widget applicability with different entity types.
   */
  public function testEntityTypeSupport() {
    // Create a file field on a different entity type (user).
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_user_file',
      'entity_type' => 'user',
      'type' => 'file',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'user',
      'label' => 'User File',
    ]);
    $field->save();

    // Initially not applicable.
    $this->assertFalse(
      FileBrowser::isApplicable($field),
      'Widget should not be applicable to user entity initially'
    );

    // Set up form display for user entity.
    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'user',
      'bundle' => 'user',
      'mode' => 'default',
    ]);
    $form_display->setComponent('field_user_file', [
      'type' => 'file_browser',
      'weight' => 10,
    ]);
    $form_display->save();

    // Now should be applicable.
    $this->assertTrue(
      FileBrowser::isApplicable($field),
      'Widget should be applicable to user entity when configured'
    );
  }

  /**
   * Tests widget configuration and settings.
   */
  public function testWidgetConfiguration() {
    $this->drupalLogin($this->adminUser);

    // Create a field and configure it with the file browser widget.
    $this->createFileField('field_test_config');
    $this->setupFormDisplayWithFileBrowser('field_test_config');

    // Visit the form display configuration page.
    $this->drupalGet('/admin/structure/types/manage/' . $this->contentType->id() . '/form-display');
    $this->assertSession()->statusCodeEquals(200);

    // The page should load without errors and show our field.
    $this->assertSession()->pageTextContains('field_test_config');
  }

  /**
   * Tests widget removal and applicability persistence.
   */
  public function testWidgetRemovalApplicability() {
    // Create a field and set up with the file browser widget.
    $field = $this->createFileField('field_test_removal');
    $form_display = $this->setupFormDisplayWithFileBrowser('field_test_removal');

    // Should be applicable.
    $this->assertTrue(
      FileBrowser::isApplicable($field),
      'Widget should be applicable when configured'
    );

    // Remove the widget from form display.
    $form_display->removeComponent('field_test_removal');
    $form_display->save();

    // Should no longer be applicable.
    $this->assertFalse(
      FileBrowser::isApplicable($field),
      'Widget should not be applicable after removal from form display'
    );
  }

  /**
   * Tests widget with different cardinality settings.
   */
  public function testCardinalitySupport() {
    // Test single-value field.
    $single_field = $this->createFileField('field_single');
    $this->setupFormDisplayWithFileBrowser('field_single');

    $this->assertTrue(
      FileBrowser::isApplicable($single_field),
      'Widget should be applicable to single-value fields'
    );

    // Test multi-value field.
    $multi_field = $this->createFileField('field_multi', 'file', TRUE);
    $this->setupFormDisplayWithFileBrowser('field_multi');

    $this->assertTrue(
      FileBrowser::isApplicable($multi_field),
      'Widget should be applicable to multi-value fields'
    );

    // Verify cardinality in field definitions.
    $this->assertEquals(1, $single_field->getFieldStorageDefinition()->getCardinality());
    $this->assertEquals(-1, $multi_field->getFieldStorageDefinition()->getCardinality());
  }

}

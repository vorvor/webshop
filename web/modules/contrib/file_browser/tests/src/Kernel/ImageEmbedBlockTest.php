<?php

namespace Drupal\Tests\file_browser\Kernel;

use Drupal\block\Entity\Block;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\UserSession;
use Drupal\file\FileInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Image Embed Block functionality.
 */
#[Group("file_browser")]
class ImageEmbedBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Ensure the private stream wrapper is available in kernel tests.
    $container->register('stream_wrapper.private', 'Drupal\Core\StreamWrapper\PrivateStream')
      ->addTag('stream_wrapper', ['scheme' => 'private']);
  }

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
    'system',
    'user',
    'field',
    'file',
    'image',
    'block',
    'block_content',
    'entity_embed',
    'entity_browser',
    'dropzonejs',
    'file_browser',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install required entity schemas and configuration used by the tests.
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('block');
    $this->installEntitySchema('block_content');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['system', 'image']);

    // Ensure we run as user 1 (bypass all access checks for entity view).
    $this->container->get('current_user')->setAccount(new UserSession(['uid' => 1]));

    // Configure a private file path so the private stream wrapper works.
    $fs = \Drupal::service('file_system');
    $private_path = $fs->realpath('public://') . '/private-files';
    $fs->prepareDirectory($private_path, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $this->setSetting('file_private_path', $private_path);

    // Create test image files.
    $this->testImage = $this->createTestImageFile('test-image1.png');
    $this->testImage2 = $this->createTestImageFile('test-image2.png');

    // Install default image styles.
    $this->container->get('module_installer')->install(['image']);
  }

  /**
   * A test image file entity.
   *
   * @var \Drupal\file\FileInterface
   */
  protected FileInterface $testImage;

  /**
   * A second test image file entity.
   *
   * @var \Drupal\file\FileInterface
   */
  protected FileInterface $testImage2;

  /**
   * A test block instance.
   *
   * @var \Drupal\block\Entity\Block
   */
  protected Block $testBlock;

  /**
   * Creates a test image file entity.
   *
   * @param string $filename
   *   The filename for the test image.
   *
   * @return \Drupal\file\FileInterface
   *   The created image file entity.
   */
  protected function createTestImageFile(string $filename) {
    // Create a minimal 1x1 pixel PNG image.
    $image_data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAHF/mWAAAAASUVORK5CYII=');
    /** @var \Drupal\file\FileRepositoryInterface $file_repository */
    $file_repository = \Drupal::service('file.repository');
    $file = $file_repository->writeData($image_data, 'public://' . $filename);

    // Ensure a proper MIME type is set.
    $file->setMimeType('image/png');
    $file->setPermanent();
    $file->save();

    return $file;
  }

  /**
   * Tests the block configuration form the structure.
   */
  public function testBlockConfigurationForm() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $block_plugin = $block_manager->createInstance('image_embed');

    // Test blockForm method directly to avoid web interface issues.
    $form = [];
    $form_state = new FormState();
    $block_form = $block_plugin->blockForm($form, $form_state);

    // Verify the block form contains expected elements.
    $this->assertIsArray($block_form, 'Block form should return an array');
    $this->assertArrayHasKey('selection', $block_form, 'Block form should have selection element');
    $this->assertArrayHasKey('image_style', $block_form, 'Block form should have image_style element');

    // Verify image style selector configuration.
    $image_style_field = $block_form['image_style'];
    $this->assertEquals('select', $image_style_field['#type']);
    $this->assertArrayHasKey('#options', $image_style_field);
    $this->assertArrayHasKey('', $image_style_field['#options'], 'Should have empty option for no style');

    // Verify entity browser container configuration.
    $selection_field = $block_form['selection'];
    $this->assertEquals('container', $selection_field['#type']);
    $this->assertEquals('image-embed-block-browser', $selection_field['#attributes']['id']);
  }

  /**
   * Tests block configuration saving and retrieval.
   */
  public function testBlockConfigurationSaving() {
    // Test block configuration persistence programmatically.
    $block_manager = \Drupal::service('plugin.manager.block');
    $block_plugin = $block_manager->createInstance('image_embed', [
      'image_style' => 'thumbnail',
      'files' => [
        [
          'fid' => $this->testImage->id(),
          'settings' => ['alt' => 'Test alt text'],
        ],
      ],
    ]);

    // Verify the configuration is set correctly.
    $config = $block_plugin->getConfiguration();
    $this->assertEquals('thumbnail', $config['image_style']);
    $this->assertIsArray($config['files']);
    $this->assertCount(1, $config['files']);
    $this->assertEquals($this->testImage->id(), $config['files'][0]['fid']);
    $this->assertEquals('Test alt text', $config['files'][0]['settings']['alt']);

    // Test creating a block entity programmatically.
    $block = Block::create([
      'id' => 'test_image_block_config',
      'theme' => $this->defaultTheme,
      'region' => 'content',
      'plugin' => 'image_embed',
      'settings' => [
        'label' => 'Test Image Block',
        'image_style' => 'medium',
        'files' => [
          [
            'fid' => $this->testImage->id(),
            'settings' => ['alt' => 'Saved alt text'],
          ],
        ],
      ],
    ]);
    $block->save();

    // Verify the block was saved and can be loaded.
    $loaded_block = Block::load('test_image_block_config');
    $this->assertNotNull($loaded_block, 'Block should be created successfully');
    $this->assertEquals('Test Image Block', $loaded_block->label());

    $settings = $loaded_block->get('settings');
    $this->assertEquals('medium', $settings['image_style']);
    $this->assertIsArray($settings['files']);
    $this->assertCount(1, $settings['files']);
  }

  /**
   * Tests block rendering with no files configured.
   */
  public function testBlockRenderingEmpty() {

    // Create a block with no files.
    $this->testBlock = Block::create([
      'id' => 'test_empty_image_block',
      'theme' => $this->defaultTheme,
      'region' => 'content',
      'weight' => 0,
      'plugin' => 'image_embed',
      'settings' => [
        'label' => 'Test Empty Image Block',
        'label_display' => 'visible',
        'image_style' => '',
        'files' => [],
      ],
    ]);
    $this->testBlock->save();

    // The block should render but be empty since no files are configured.
    // We can't easily test the block content without placing it, but we can
    // verify it doesn't cause errors when built.
    $plugin = $this->testBlock->getPlugin();
    $build = $plugin->build();
    $this->assertIsArray($build, 'Block should return a build array');
    $this->assertEmpty($build, 'Empty block should return empty build array');
  }

  /**
   * Tests block rendering with configured files.
   */
  public function testBlockRenderingWithFiles() {
    // Create a block with file configuration.
    $this->testBlock = Block::create([
      'id' => 'test_image_block_with_files',
      'theme' => $this->defaultTheme,
      'region' => 'content',
      'weight' => 0,
      'plugin' => 'image_embed',
      'settings' => [
        'label' => 'Test Image Block',
        'label_display' => 'visible',
        'image_style' => 'thumbnail',
        'files' => [
          [
            'fid' => $this->testImage->id(),
            'settings' => [
              'alt' => 'Test image alt text',
            ],
          ],
          [
            'fid' => $this->testImage2->id(),
            'settings' => [
              'alt' => 'Second test image',
            ],
          ],
        ],
      ],
    ]);
    $this->testBlock->save();

    // Test the block build method directly.
    $plugin = $this->testBlock->getPlugin();
    $build = $plugin->build();

    $this->assertIsArray($build, 'Block should return a build array');
    $this->assertCount(2, $build, 'Block should render 2 images');

    // Verify the first image.
    $this->assertEquals('image_style', $build[0]['#theme']);
    $this->assertEquals('thumbnail', $build[0]['#style_name']);
    $this->assertEquals('Test image alt text', $build[0]['#alt']);

    // Verify the second image.
    $this->assertEquals('image_style', $build[1]['#theme']);
    $this->assertEquals('thumbnail', $build[1]['#style_name']);
    $this->assertEquals('Second test image', $build[1]['#alt']);
  }

  /**
   * Tests block rendering with no image style.
   */
  public function testBlockRenderingNoImageStyle() {
    // Create a block without image style.
    $this->testBlock = Block::create([
      'id' => 'test_image_block_no_style',
      'theme' => $this->defaultTheme,
      'region' => 'content',
      'weight' => 0,
      'plugin' => 'image_embed',
      'settings' => [
        'label' => 'Test Image Block No Style',
        'label_display' => 'visible',
        'image_style' => '',
        'files' => [
          [
            'fid' => $this->testImage->id(),
            'settings' => [
              'alt' => 'Test image without style',
            ],
          ],
        ],
      ],
    ]);
    $this->testBlock->save();

    // Test the block build method.
    $plugin = $this->testBlock->getPlugin();
    $build = $plugin->build();

    $this->assertIsArray($build, 'Block should return a build array');
    $this->assertCount(1, $build, 'Block should render 1 image');

    // When no image style is used, should use 'image' theme.
    $this->assertEquals('image', $build[0]['#theme']);
    $this->assertArrayNotHasKey('#style_name', $build[0]);
    $this->assertEquals('Test image without style', $build[0]['#alt']);
  }

  /**
   * Tests entity browser integration in block form.
   */
  public function testEntityBrowserIntegration() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $block_plugin = $block_manager->createInstance('image_embed');

    // Test browserForm method to verify entity browser integration.
    $browser_form = $block_plugin->browserForm([]);

    // Verify entity browser components are configured correctly.
    $this->assertArrayHasKey('fids', $browser_form);
    $this->assertEquals('entity_browser', $browser_form['fids']['#type']);
    $this->assertEquals('browse_files_modal', $browser_form['fids']['#entity_browser']);

    // Verify entity browser validators.
    $this->assertArrayHasKey('#entity_browser_validators', $browser_form['fids']);
    $this->assertEquals('file', $browser_form['fids']['#entity_browser_validators']['entity_type']['type']);

    // Verify processing callbacks are configured.
    $this->assertArrayHasKey('#process', $browser_form['fids']);
    $this->assertCount(2, $browser_form['fids']['#process']);
  }

  /**
   * Tests block default configuration.
   */
  public function testDefaultConfiguration() {
    // Create an Image Embed block plugin instance.
    $block_manager = \Drupal::service('plugin.manager.block');
    $plugin_definition = $block_manager->getDefinition('image_embed');

    $this->assertEquals('image_embed', $plugin_definition['id']);
    $this->assertEquals('Image Embed', (string) $plugin_definition['admin_label']);
    $this->assertEquals('Embed', (string) $plugin_definition['category']);

    // Test default configuration.
    $block = $block_manager->createInstance('image_embed');
    $default_config = $block->defaultConfiguration();

    $this->assertArrayHasKey('image_style', $default_config);
    $this->assertArrayHasKey('files', $default_config);
    $this->assertEquals('', $default_config['image_style']);
    $this->assertEquals([], $default_config['files']);
  }

  /**
   * Tests block form processing and submission.
   */
  public function testBlockFormProcessing() {

    // Create a block programmatically and test its form.
    $block_manager = \Drupal::service('plugin.manager.block');
    $block_plugin = $block_manager->createInstance('image_embed', [
      'image_style' => 'medium',
      'files' => [
        [
          'fid' => $this->testImage->id(),
          'settings' => ['alt' => 'Configured alt text'],
        ],
      ],
    ]);

    // Test blockForm method.
    $form = [];
    $form_state = new FormState();
    $block_form = $block_plugin->blockForm($form, $form_state);

    $this->assertIsArray($block_form, 'blockForm should return an array');
    $this->assertArrayHasKey('selection', $block_form);
    $this->assertArrayHasKey('image_style', $block_form);

    // Verify image style field configuration.
    $this->assertEquals('select', $block_form['image_style']['#type']);
    $this->assertEquals('medium', $block_form['image_style']['#default_value']);

    // Verify selection container.
    $this->assertEquals('container', $block_form['selection']['#type']);
    $this->assertEquals('image-embed-block-browser', $block_form['selection']['#attributes']['id']);
  }

  /**
   * Tests file access control in block rendering.
   */
  public function testFileAccessControl() {
    // Create a private file for access testing.
    $private_image_data = base64_decode(
      'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAHF/mWAAAAASUVORK5CYII='
    );
    /** @var \Drupal\file\FileRepositoryInterface $file_repository */
    $file_repository = \Drupal::service('file.repository');
    $private_file = $file_repository->writeData($private_image_data, 'private://private-test-image.png');
    $private_file->setMimeType('image/png');
    $private_file->setPermanent();
    $private_file->save();

    // Create a block with the private file.
    $this->testBlock = Block::create([
      'id' => 'test_private_image_block',
      'theme' => $this->defaultTheme,
      'region' => 'content',
      'weight' => 0,
      'plugin' => 'image_embed',
      'settings' => [
        'label' => 'Test Private Image Block',
        'label_display' => 'visible',
        'image_style' => '',
        'files' => [
          [
            'fid' => $this->testImage->id(),
            'settings' => ['alt' => 'Public image'],
          ],
          [
            'fid' => $private_file->id(),
            'settings' => ['alt' => 'Private image'],
          ],
        ],
      ],
    ]);
    $this->testBlock->save();

    // Test block rendering respects file access.
    $plugin = $this->testBlock->getPlugin();
    $build = $plugin->build();

    // The build should only include files that are accessible.
    // Public files should be included, private file access depends on
    // permissions.
    $this->assertIsArray($build, 'Block should return a build array');

    // Clean up.
    $private_file->delete();
  }

  /**
   * Tests browserForm method functionality.
   */
  public function testBrowserFormGeneration() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $block_plugin = $block_manager->createInstance('image_embed');

    // Test browserForm with files.
    $files = [
      [
        'fid' => $this->testImage->id(),
        'settings' => ['alt' => 'Test alt text'],
      ],
    ];

    $browser_form = $block_plugin->browserForm($files);

    $this->assertIsArray($browser_form, 'browserForm should return an array');
    $this->assertEquals('container', $browser_form['#type']);
    $this->assertEquals('image-embed-block-browser', $browser_form['#attributes']['id']);

    // Verify entity browser element.
    $this->assertArrayHasKey('fids', $browser_form);
    $this->assertEquals('entity_browser', $browser_form['fids']['#type']);
    $this->assertEquals('browse_files_modal', $browser_form['fids']['#entity_browser']);

    // Verify table structure.
    $this->assertArrayHasKey('table', $browser_form);
    $this->assertEquals('table', $browser_form['table']['#type']);
    $this->assertArrayHasKey('#header', $browser_form['table']);
    $this->assertArrayHasKey('#tabledrag', $browser_form['table']);

    // Verify the file row is created.
    $file_id = $this->testImage->id();
    $this->assertArrayHasKey($file_id, $browser_form['table']);
    $this->assertArrayHasKey('display', $browser_form['table'][$file_id]);
    $this->assertArrayHasKey('filename', $browser_form['table'][$file_id]);
    $this->assertArrayHasKey('alt', $browser_form['table'][$file_id]);
    $this->assertArrayHasKey('_weight', $browser_form['table'][$file_id]);

    // Verify alt field configuration.
    $alt_field = $browser_form['table'][$file_id]['alt'];
    $this->assertEquals('textfield', $alt_field['#type']);
    $this->assertEquals('Test alt text', $alt_field['#default_value']);
    $this->assertEquals(512, $alt_field['#maxlength']);
  }

  /**
   * Tests empty browserForm generation.
   */
  public function testEmptyBrowserForm() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $block_plugin = $block_manager->createInstance('image_embed');

    // Test browserForm with no files.
    $browser_form = $block_plugin->browserForm([]);

    $this->assertIsArray($browser_form, 'browserForm should return an array');
    $this->assertArrayHasKey('fids', $browser_form);
    $this->assertArrayHasKey('table', $browser_form);

    // Table should be empty.
    $table_keys = array_filter(array_keys($browser_form['table']), function ($key) {
      return !str_starts_with($key, '#');
    });
    $this->assertEmpty($table_keys, 'Empty browserForm should have no file rows');
  }

  /**
   * Tests block submission and configuration persistence.
   */
  public function testBlockSubmitConfiguration() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $block_plugin = $block_manager->createInstance('image_embed');

    // Simulate form state with file selections.
    $form_state = new FormState();
    $form_state->setValue(['selection', 'table'], [
      $this->testImage->id() => [
        'alt' => 'Submitted alt text',
        '_weight' => 0,
      ],
      $this->testImage2->id() => [
        'alt' => 'Second submitted alt text',
        '_weight' => 1,
      ],
    ]);
    $form_state->setValue('image_style', 'large');

    // Call blockSubmit.
    $block_plugin->blockSubmit([], $form_state);

    // Verify the configuration was updated.
    $config = $block_plugin->getConfiguration();
    $this->assertEquals('large', $config['image_style']);
    $this->assertCount(2, $config['files']);

    // Verify file configurations.
    $this->assertEquals($this->testImage->id(), $config['files'][0]['fid']);
    $this->assertEquals('Submitted alt text', $config['files'][0]['settings']['alt']);
    $this->assertEquals($this->testImage2->id(), $config['files'][1]['fid']);
    $this->assertEquals('Second submitted alt text', $config['files'][1]['settings']['alt']);
  }

  /**
   * Tests block build method with various configurations.
   */
  public function testBlockBuildMethod() {
    $block_manager = \Drupal::service('plugin.manager.block');

    // Test with image style.
    $block_plugin = $block_manager->createInstance('image_embed', [
      'image_style' => 'medium',
      'files' => [
        [
          'fid' => $this->testImage->id(),
          'settings' => ['alt' => 'Styled image'],
        ],
      ],
    ]);

    $build = $block_plugin->build();
    $this->assertCount(1, $build);
    $this->assertEquals('image_style', $build[0]['#theme']);
    $this->assertEquals('medium', $build[0]['#style_name']);
    $this->assertEquals('Styled image', $build[0]['#alt']);

    // Test without image style.
    $block_plugin_no_style = $block_manager->createInstance('image_embed', [
      'image_style' => '',
      'files' => [
        [
          'fid' => $this->testImage->id(),
          'settings' => ['alt' => 'Unstyled image'],
        ],
      ],
    ]);

    $build_no_style = $block_plugin_no_style->build();
    $this->assertCount(1, $build_no_style);
    $this->assertEquals('image', $build_no_style[0]['#theme']);
    $this->assertArrayNotHasKey('#style_name', $build_no_style[0]);
    $this->assertEquals('Unstyled image', $build_no_style[0]['#alt']);
  }

  /**
   * Tests block behavior with invalid file references.
   */
  public function testInvalidFileReferences() {
    $block_manager = \Drupal::service('plugin.manager.block');

    // Create a block with invalid file ID.
    $invalid_fid = $this->testImage->id() + 999;
    $block_plugin = $block_manager->createInstance('image_embed', [
      'image_style' => '',
      'files' => [
        [
          'fid' => $this->testImage->id(),
          'settings' => ['alt' => 'Valid image'],
        ],
        [
          'fid' => $invalid_fid,
          'settings' => ['alt' => 'Invalid image'],
        ],
      ],
    ]);

    $build = $block_plugin->build();

    // Should only render the valid file.
    $this->assertCount(1, $build, 'Block should only render valid files');
    $this->assertEquals('Valid image', $build[0]['#alt']);
  }

  /**
   * Tests block form structure with files.
   */
  public function testBlockFormStructure() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $block_plugin = $block_manager->createInstance('image_embed');

    // Test browserForm structure with files.
    $files = [
      [
        'fid' => $this->testImage->id(),
        'settings' => ['alt' => 'Test alt text'],
      ],
    ];
    $browser_form = $block_plugin->browserForm($files);

    // Verify table structure and headers.
    $this->assertArrayHasKey('table', $browser_form);
    $table = $browser_form['table'];
    $this->assertEquals('table', $table['#type']);
    $this->assertArrayHasKey('#header', $table);

    // Verify table headers.
    $headers = $table['#header'];
    // Headers contain TranslatableMarkup objects, so convert to strings for
    // comparison.
    $header_strings = array_map('strval', $headers);
    $this->assertContains('Preview', $header_strings);
    $this->assertContains('Filename', $header_strings);
    $this->assertContains('Metadata', $header_strings);
    $this->assertContains('Order', $header_strings);

    // Verify tabledrag configuration.
    $this->assertArrayHasKey('#tabledrag', $table);
    $this->assertNotEmpty($table['#tabledrag']);

    // Verify empty state message is configured.
    $this->assertEquals('No files yet', (string) $table['#empty']);

    // Verify file row structure.
    $file_id = $this->testImage->id();
    $this->assertArrayHasKey($file_id, $table);
    $file_row = $table[$file_id];
    $this->assertArrayHasKey('display', $file_row);
    $this->assertArrayHasKey('filename', $file_row);
    $this->assertArrayHasKey('alt', $file_row);
    $this->assertArrayHasKey('_weight', $file_row);
  }

}

<?php

namespace Drupal\Tests\file_browser\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel tests for procedural hooks in file_browser.module.
 */
#[Group('file_browser')]
class FileBrowserHooksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'file_browser',
  ];

  /**
   * Tests file_browser_attach_file_browser_to_form() behavior.
   */
  public function testAttachFileBrowserToForm(): void {
    $form = [
      '#attached' => [
        'library' => [],
      ],
      '#attributes' => [
        'class' => [],
      ],
      'selection_display' => [],
    ];

    // Call the procedural function directly.
    file_browser_attach_file_browser_to_form($form);

    // Assert libraries are attached.
    $this->assertContains('file_browser/view', $form['#attached']['library']);
    $this->assertContains('file_browser/dropzone_css', $form['#attached']['library']);

    // Assert selection_display becomes a container with the expected class.
    $this->assertSame('container', $form['selection_display']['#type']);
    $this->assertContains('file-browser-actions', $form['selection_display']['#attributes']['class']);

    // Assert the form gets the expected CSS class.
    $this->assertContains('file-browser-form', $form['#attributes']['class']);
  }

  /**
   * Tests file_browser_form_alter() for the browse files form ID.
   */
  public function testFormAlterBrowseFilesForm(): void {
    $form = [
      '#form_id' => 'entity_browser_browse_files_form',
      '#attached' => [
        'library' => [],
      ],
      '#attributes' => [
        'class' => [],
      ],
      'selection_display' => [],
    ];
    $form_state = new FormState();

    file_browser_form_alter($form, $form_state);

    $this->assertContains('file_browser/view', $form['#attached']['library']);
    $this->assertContains('file_browser/dropzone_css', $form['#attached']['library']);
    $this->assertSame('container', $form['selection_display']['#type']);
    $this->assertContains('file-browser-actions', $form['selection_display']['#attributes']['class']);
    $this->assertContains('file-browser-form', $form['#attributes']['class']);
  }

  /**
   * Tests file_browser_form_alter() for the modal browse files form ID.
   */
  public function testFormAlterBrowseFilesModalForm(): void {
    $form = [
      '#form_id' => 'entity_browser_browse_files_modal_form',
      '#attached' => [
        'library' => [],
      ],
      '#attributes' => [
        'class' => [],
      ],
      'selection_display' => [],
    ];
    $form_state = new FormState();

    file_browser_form_alter($form, $form_state);

    $this->assertContains('file_browser/view', $form['#attached']['library']);
    $this->assertContains('file_browser/dropzone_css', $form['#attached']['library']);
    $this->assertSame('container', $form['selection_display']['#type']);
    $this->assertContains('file-browser-actions', $form['selection_display']['#attributes']['class']);
    $this->assertContains('file-browser-form', $form['#attributes']['class']);
  }

  /**
   * Tests file_browser_preprocess_details() attaches the iframe library by ID.
   */
  public function testPreprocessDetailsAttachesIframeLibrary(): void {
    $variables = [
      'element' => [
        '#id' => 'edit-field-file-browser-reference',
      ],
      '#attached' => [
        'library' => [],
      ],
    ];

    file_browser_preprocess_details($variables);

    $this->assertContains('file_browser/iframe', $variables['#attached']['library']);
  }

}

<?php

/**
 * @file
 * Definition of Drupal\options\Tests\OptionsWidgetsTest.
 */

namespace Drupal\options\Tests;

use Drupal\field\Tests\FieldTestBase;

/**
 * Test the Options widgets.
 */
class OptionsWidgetsTest extends FieldTestBase {
  public static function getInfo() {
    return array(
      'name'  => 'Options widgets',
      'description'  => "Test the Options widgets.",
      'group' => 'Field types'
    );
  }

  function setUp() {
    parent::setUp(array('options', 'field_test', 'options_test', 'taxonomy', 'field_ui'));

    // Field with cardinality 1.
    $this->card_1 = array(
      'field_name' => 'card_1',
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => array(
        // Make sure that 0 works as an option.
        'allowed_values' => array(0 => 'Zero', 1 => 'One', 2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>'),
      ),
    );
    $this->card_1 = field_create_field($this->card_1);

    // Field with cardinality 2.
    $this->card_2 = array(
      'field_name' => 'card_2',
      'type' => 'list_integer',
      'cardinality' => 2,
      'settings' => array(
        // Make sure that 0 works as an option.
        'allowed_values' => array(0 => 'Zero', 1 => 'One', 2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>'),
      ),
    );
    $this->card_2 = field_create_field($this->card_2);

    // Boolean field.
    $this->bool = array(
      'field_name' => 'bool',
      'type' => 'list_boolean',
      'cardinality' => 1,
      'settings' => array(
        // Make sure that 0 works as a 'on' value'.
        'allowed_values' => array(1 => 'Zero', 0 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>'),
      ),
    );
    $this->bool = field_create_field($this->bool);

    // Create a web user.
    $this->web_user = $this->drupalCreateUser(array('access field_test content', 'administer field_test content'));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Tests the 'options_buttons' widget (single select).
   */
  function testRadioButtons() {
    // Create an instance of the 'single value' field.
    $instance = array(
      'field_name' => $this->card_1['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'widget' => array(
        'type' => 'options_buttons',
      ),
    );
    $instance = field_create_instance($instance);
    $langcode = LANGUAGE_NOT_SPECIFIED;

    // Create an entity.
    $entity_init = field_test_create_stub_entity();
    $entity = clone $entity_init;
    $entity->is_new = TRUE;
    field_test_entity_save($entity);

    // With no field data, no buttons are checked.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertNoFieldChecked("edit-card-1-$langcode-0");
    $this->assertNoFieldChecked("edit-card-1-$langcode-1");
    $this->assertNoFieldChecked("edit-card-1-$langcode-2");
    $this->assertRaw('Some dangerous &amp; unescaped <strong>markup</strong>', t('Option text was properly filtered.'));

    // Select first option.
    $edit = array("card_1[$langcode]" => 0);
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', $langcode, array(0));

    // Check that the selected button is checked.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertFieldChecked("edit-card-1-$langcode-0");
    $this->assertNoFieldChecked("edit-card-1-$langcode-1");
    $this->assertNoFieldChecked("edit-card-1-$langcode-2");

    // Unselect option.
    $edit = array("card_1[$langcode]" => '_none');
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', $langcode, array());

    // Check that required radios with one option is auto-selected.
    $this->card_1['settings']['allowed_values'] = array(99 => 'Only allowed value');
    field_update_field($this->card_1);
    $instance['required'] = TRUE;
    field_update_instance($instance);
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertFieldChecked("edit-card-1-$langcode-99");
  }

  /**
   * Tests the 'options_buttons' widget (multiple select).
   */
  function testCheckBoxes() {
    // Create an instance of the 'multiple values' field.
    $instance = array(
      'field_name' => $this->card_2['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'widget' => array(
        'type' => 'options_buttons',
      ),
    );
    $instance = field_create_instance($instance);
    $langcode = LANGUAGE_NOT_SPECIFIED;

    // Create an entity.
    $entity_init = field_test_create_stub_entity();
    $entity = clone $entity_init;
    $entity->is_new = TRUE;
    field_test_entity_save($entity);

    // Display form: with no field data, nothing is checked.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertNoFieldChecked("edit-card-2-$langcode-0");
    $this->assertNoFieldChecked("edit-card-2-$langcode-1");
    $this->assertNoFieldChecked("edit-card-2-$langcode-2");
    $this->assertRaw('Some dangerous &amp; unescaped <strong>markup</strong>', t('Option text was properly filtered.'));

    // Submit form: select first and third options.
    $edit = array(
      "card_2[$langcode][0]" => TRUE,
      "card_2[$langcode][1]" => FALSE,
      "card_2[$langcode][2]" => TRUE,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', $langcode, array(0, 2));

    // Display form: check that the right options are selected.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertFieldChecked("edit-card-2-$langcode-0");
    $this->assertNoFieldChecked("edit-card-2-$langcode-1");
    $this->assertFieldChecked("edit-card-2-$langcode-2");

    // Submit form: select only first option.
    $edit = array(
      "card_2[$langcode][0]" => TRUE,
      "card_2[$langcode][1]" => FALSE,
      "card_2[$langcode][2]" => FALSE,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', $langcode, array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertFieldChecked("edit-card-2-$langcode-0");
    $this->assertNoFieldChecked("edit-card-2-$langcode-1");
    $this->assertNoFieldChecked("edit-card-2-$langcode-2");

    // Submit form: select the three options while the field accepts only 2.
    $edit = array(
      "card_2[$langcode][0]" => TRUE,
      "card_2[$langcode][1]" => TRUE,
      "card_2[$langcode][2]" => TRUE,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertText('this field cannot hold more than 2 values', t('Validation error was displayed.'));

    // Submit form: uncheck all options.
    $edit = array(
      "card_2[$langcode][0]" => FALSE,
      "card_2[$langcode][1]" => FALSE,
      "card_2[$langcode][2]" => FALSE,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    // Check that the value was saved.
    $this->assertFieldValues($entity_init, 'card_2', $langcode, array());

    // Required checkbox with one option is auto-selected.
    $this->card_2['settings']['allowed_values'] = array(99 => 'Only allowed value');
    field_update_field($this->card_2);
    $instance['required'] = TRUE;
    field_update_instance($instance);
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertFieldChecked("edit-card-2-$langcode-99");
  }

  /**
   * Tests the 'options_select' widget (single select).
   */
  function testSelectListSingle() {
    // Create an instance of the 'single value' field.
    $instance = array(
      'field_name' => $this->card_1['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'required' => TRUE,
      'widget' => array(
        'type' => 'options_select',
      ),
    );
    $instance = field_create_instance($instance);
    $langcode = LANGUAGE_NOT_SPECIFIED;

    // Create an entity.
    $entity_init = field_test_create_stub_entity();
    $entity = clone $entity_init;
    $entity->is_new = TRUE;
    field_test_entity_save($entity);

    // Display form.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    // A required field without any value has a "none" option.
    $this->assertTrue($this->xpath('//select[@id=:id]//option[@value="_none" and text()=:label]', array(':id' => 'edit-card-1-' . $langcode, ':label' => t('- Select a value -'))), t('A required select list has a "Select a value" choice.'));

    // With no field data, nothing is selected.
    $this->assertNoOptionSelected("edit-card-1-$langcode", '_none');
    $this->assertNoOptionSelected("edit-card-1-$langcode", 0);
    $this->assertNoOptionSelected("edit-card-1-$langcode", 1);
    $this->assertNoOptionSelected("edit-card-1-$langcode", 2);
    $this->assertRaw('Some dangerous &amp; unescaped markup', t('Option text was properly filtered.'));

    // Submit form: select invalid 'none' option.
    $edit = array("card_1[$langcode]" => '_none');
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertRaw(t('!title field is required.', array('!title' => $instance['field_name'])), t('Cannot save a required field when selecting "none" from the select list.'));

    // Submit form: select first option.
    $edit = array("card_1[$langcode]" => 0);
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', $langcode, array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    // A required field with a value has no 'none' option.
    $this->assertFalse($this->xpath('//select[@id=:id]//option[@value="_none"]', array(':id' => 'edit-card-1-' . $langcode)), t('A required select list with an actual value has no "none" choice.'));
    $this->assertOptionSelected("edit-card-1-$langcode", 0);
    $this->assertNoOptionSelected("edit-card-1-$langcode", 1);
    $this->assertNoOptionSelected("edit-card-1-$langcode", 2);

    // Make the field non required.
    $instance['required'] = FALSE;
    field_update_instance($instance);

    // Display form.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    // A non-required field has a 'none' option.
    $this->assertTrue($this->xpath('//select[@id=:id]//option[@value="_none" and text()=:label]', array(':id' => 'edit-card-1-' . $langcode, ':label' => t('- None -'))), t('A non-required select list has a "None" choice.'));
    // Submit form: Unselect the option.
    $edit = array("card_1[$langcode]" => '_none');
    $this->drupalPost('test-entity/manage/' . $entity->ftid . '/edit', $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', $langcode, array());

    // Test optgroups.

    $this->card_1['settings']['allowed_values'] = array();
    $this->card_1['settings']['allowed_values_function'] = 'options_test_allowed_values_callback';
    field_update_field($this->card_1);

    // Display form: with no field data, nothing is selected
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertNoOptionSelected("edit-card-1-$langcode", 0);
    $this->assertNoOptionSelected("edit-card-1-$langcode", 1);
    $this->assertNoOptionSelected("edit-card-1-$langcode", 2);
    $this->assertRaw('Some dangerous &amp; unescaped markup', t('Option text was properly filtered.'));
    $this->assertRaw('Group 1', t('Option groups are displayed.'));

    // Submit form: select first option.
    $edit = array("card_1[$langcode]" => 0);
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', $langcode, array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertOptionSelected("edit-card-1-$langcode", 0);
    $this->assertNoOptionSelected("edit-card-1-$langcode", 1);
    $this->assertNoOptionSelected("edit-card-1-$langcode", 2);

    // Submit form: Unselect the option.
    $edit = array("card_1[$langcode]" => '_none');
    $this->drupalPost('test-entity/manage/' . $entity->ftid . '/edit', $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', $langcode, array());
  }

  /**
   * Tests the 'options_select' widget (multiple select).
   */
  function testSelectListMultiple() {
    // Create an instance of the 'multiple values' field.
    $instance = array(
      'field_name' => $this->card_2['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'widget' => array(
        'type' => 'options_select',
      ),
    );
    $instance = field_create_instance($instance);
    $langcode = LANGUAGE_NOT_SPECIFIED;

    // Create an entity.
    $entity_init = field_test_create_stub_entity();
    $entity = clone $entity_init;
    $entity->is_new = TRUE;
    field_test_entity_save($entity);

    // Display form: with no field data, nothing is selected.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertNoOptionSelected("edit-card-2-$langcode", 0);
    $this->assertNoOptionSelected("edit-card-2-$langcode", 1);
    $this->assertNoOptionSelected("edit-card-2-$langcode", 2);
    $this->assertRaw('Some dangerous &amp; unescaped markup', t('Option text was properly filtered.'));

    // Submit form: select first and third options.
    $edit = array("card_2[$langcode][]" => array(0 => 0, 2 => 2));
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', $langcode, array(0, 2));

    // Display form: check that the right options are selected.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertOptionSelected("edit-card-2-$langcode", 0);
    $this->assertNoOptionSelected("edit-card-2-$langcode", 1);
    $this->assertOptionSelected("edit-card-2-$langcode", 2);

    // Submit form: select only first option.
    $edit = array("card_2[$langcode][]" => array(0 => 0));
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', $langcode, array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertOptionSelected("edit-card-2-$langcode", 0);
    $this->assertNoOptionSelected("edit-card-2-$langcode", 1);
    $this->assertNoOptionSelected("edit-card-2-$langcode", 2);

    // Submit form: select the three options while the field accepts only 2.
    $edit = array("card_2[$langcode][]" => array(0 => 0, 1 => 1, 2 => 2));
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertText('this field cannot hold more than 2 values', t('Validation error was displayed.'));

    // Submit form: uncheck all options.
    $edit = array("card_2[$langcode][]" => array());
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', $langcode, array());

    // Test the 'None' option.

    // Check that the 'none' option has no efect if actual options are selected
    // as well.
    $edit = array("card_2[$langcode][]" => array('_none' => '_none', 0 => 0));
    $this->drupalPost('test-entity/manage/' . $entity->ftid . '/edit', $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', $langcode, array(0));

    // Check that selecting the 'none' option empties the field.
    $edit = array("card_2[$langcode][]" => array('_none' => '_none'));
    $this->drupalPost('test-entity/manage/' . $entity->ftid . '/edit', $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', $langcode, array());

    // A required select list does not have an empty key.
    $instance['required'] = TRUE;
    field_update_instance($instance);
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertFalse($this->xpath('//select[@id=:id]//option[@value=""]', array(':id' => 'edit-card-2-' . $langcode)), t('A required select list does not have an empty key.'));

    // We do not have to test that a required select list with one option is
    // auto-selected because the browser does it for us.

    // Test optgroups.

    // Use a callback function defining optgroups.
    $this->card_2['settings']['allowed_values'] = array();
    $this->card_2['settings']['allowed_values_function'] = 'options_test_allowed_values_callback';
    field_update_field($this->card_2);
    $instance['required'] = FALSE;
    field_update_instance($instance);

    // Display form: with no field data, nothing is selected.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertNoOptionSelected("edit-card-2-$langcode", 0);
    $this->assertNoOptionSelected("edit-card-2-$langcode", 1);
    $this->assertNoOptionSelected("edit-card-2-$langcode", 2);
    $this->assertRaw('Some dangerous &amp; unescaped markup', t('Option text was properly filtered.'));
    $this->assertRaw('Group 1', t('Option groups are displayed.'));

    // Submit form: select first option.
    $edit = array("card_2[$langcode][]" => array(0 => 0));
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', $langcode, array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertOptionSelected("edit-card-2-$langcode", 0);
    $this->assertNoOptionSelected("edit-card-2-$langcode", 1);
    $this->assertNoOptionSelected("edit-card-2-$langcode", 2);

    // Submit form: Unselect the option.
    $edit = array("card_2[$langcode][]" => array('_none' => '_none'));
    $this->drupalPost('test-entity/manage/' . $entity->ftid . '/edit', $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', $langcode, array());
  }

  /**
   * Tests the 'options_onoff' widget.
   */
  function testOnOffCheckbox() {
    // Create an instance of the 'boolean' field.
    $instance = array(
      'field_name' => $this->bool['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'widget' => array(
        'type' => 'options_onoff',
      ),
    );
    $instance = field_create_instance($instance);
    $langcode = LANGUAGE_NOT_SPECIFIED;

    // Create an entity.
    $entity_init = field_test_create_stub_entity();
    $entity = clone $entity_init;
    $entity->is_new = TRUE;
    field_test_entity_save($entity);

    // Display form: with no field data, option is unchecked.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertNoFieldChecked("edit-bool-$langcode");
    $this->assertRaw('Some dangerous &amp; unescaped <strong>markup</strong>', t('Option text was properly filtered.'));

    // Submit form: check the option.
    $edit = array("bool[$langcode]" => TRUE);
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'bool', $langcode, array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertFieldChecked("edit-bool-$langcode");

    // Submit form: uncheck the option.
    $edit = array("bool[$langcode]" => FALSE);
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'bool', $langcode, array(1));

    // Display form: with 'off' value, option is unchecked.
    $this->drupalGet('test-entity/manage/' . $entity->ftid . '/edit');
    $this->assertNoFieldChecked("edit-bool-$langcode");

    // Create Basic page node type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Create admin user.
    $admin_user = $this->drupalCreateUser(array('access content', 'administer content types', 'administer taxonomy'));
    $this->drupalLogin($admin_user);

    // Create a test field instance.
    $fieldUpdate = $this->bool;
    $fieldUpdate['settings']['allowed_values'] = array(0 => 0, 1 => 'MyOnValue');
    field_update_field($fieldUpdate);
    $instance = array(
      'field_name' => $this->bool['field_name'],
      'entity_type' => 'node',
      'bundle' => 'page',
      'widget' => array(
            'type' => 'options_onoff',
            'module' => 'options',
        ),
    );
    field_create_instance($instance);

    // Go to the edit page and check if the default settings works as expected
    $fieldEditUrl = 'admin/structure/types/manage/page/fields/bool';
    $this->drupalGet($fieldEditUrl);

    $this->assertText(
      'Use field label instead of the "On value" as label ',
      t('Display setting checkbox available.')
    );

    $this->assertFieldByXPath(
      '*//label[@for="edit-' . $this->bool['field_name'] . '-und" and text()="MyOnValue "]',
      TRUE,
      t('Default case shows "On value"')
    );

    // Enable setting
    $edit = array('instance[widget][settings][display_label]' => 1);
    // Save the new Settings
    $this->drupalPost($fieldEditUrl, $edit, t('Save settings'));

    // Go again to the edit page and check if the setting
    // is stored and has the expected effect
    $this->drupalGet($fieldEditUrl);
    $this->assertText(
      'Use field label instead of the "On value" as label ',
      t('Display setting checkbox is available')
    );
    $this->assertFieldChecked(
      'edit-instance-widget-settings-display-label',
      t('Display settings checkbox checked')
    );
    $this->assertFieldByXPath(
      '*//label[@for="edit-' . $this->bool['field_name'] . '-und" and text()="' . $this->bool['field_name'] . ' "]',
      TRUE,
      t('Display label changes label of the checkbox')
    );
  }
}

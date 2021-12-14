<?php

class SNP_NHP_Options_multi_key_value_text extends SNP_NHP_Options
{
    public function __construct($field = array(), $value ='', $parent)
    {
        parent::__construct($parent->sections, $parent->args, $parent->extra_tabs);

        $this->field = $field;
        $this->value = $value;
    }

    public function render()
    {
        $class = (isset($this->field['class'])) ? $this->field['class'] : 'regular-text';


        echo '<table id="'.$this->field['id'].'-table" style="width: 100%;">';
        echo '<tr>';
        echo '<td><strong>Key</strong></td><td><strong>Value</strong></td><td></td>';
        echo '</tr>';


        if (isset($this->value) && is_array($this->value)) {
            for ($i = 0; $i <= count($this->value['key']);  $i++) {
                if (empty($this->value['key'][$i])) {
                    continue;
                }
                echo '<tr>';

                echo '
                <td>
                    <input type="text" name="'.$this->args['opt_name'].'['.$this->field['id'].'][key][]" value="'.$this->value['key'][$i].'" class="regular-text">
                </td>
                ';

                echo '
                <td>
                    <input type="text" name="'.$this->args['opt_name'].'['.$this->field['id'].'][value][]" value="'.$this->value['value'][$i].'" class="regular-text">
                </td>
                ';

                echo '
                <td>
                    <input type="button" class="nhp-opts-multi-key-value-text-remove button" value="'.__('Remove', 'nhp-opts').'" />
                </td>';

                echo '</tr>';
            }
        }

        echo '
            <tr class="template snp-d-none">
                <td>
                    <input type="text" name="'.$this->args['opt_name'].'['.$this->field['id'].'][key][]" value="" class="regular-text">
                </td>
                <td>
                    <input type="text" name="'.$this->args['opt_name'].'['.$this->field['id'].'][value][]" value="" class="regular-text">
                </td>
                <td>
                    <input type="button" class="nhp-opts-multi-key-value-text-remove button" value="'.__('Remove', 'nhp-opts').'" />
                </td>
            </tr>
        </table>';

        echo '<input type="button" class="nhp-opts-multi-key-value-text-add button" rel-id="'.$this->field['id'].'-table" value="'.__('Add More', 'nhp-opts').'" />';
        echo '<br/>';

        echo (isset($this->field['desc']) && !empty($this->field['desc']))?' <span class="description">'.$this->field['desc'].'</span>':'';
    }

    public function enqueue()
    {
        wp_enqueue_script('nhp-opts-field-multi-key-value-text-js', SNP_NHP_OPTIONS_URL.'fields/multi_key_value_text/field_multi_key_value_text.js', array('jquery'), time(), true);
    }
}
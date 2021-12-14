<?php

class SNP_NHP_Options_getresponse_fields extends SNP_NHP_Options
{
    public function __construct($field = array(), $value ='', $parent)
    {
        parent::__construct($parent->sections, $parent->args, $parent->extra_tabs);

        $this->field = $field;
        $this->value = $value;
    }

    public function render()
    {
        $fields = snp_ml_get_gr_fields();

        echo '<table>';

        foreach ($fields as $fieldId => $fieldName) {
            echo '<tr>';
            echo '<td>' . $fieldId . '</td><td>' . $fieldName['name'] . '</td>';
            echo '</tr>';
        }

        echo '</table>';

        echo (isset($this->field['desc']) && !empty($this->field['desc'])) ? ' <span class="description">' . $this->field['desc'] . '</span>' : '';
    }

    public function enqueue() {}
}
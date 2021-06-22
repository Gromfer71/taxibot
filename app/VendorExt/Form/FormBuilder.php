<?php
namespace App\VendorExt\Form;

use App\Services\Role;
use Illuminate\Support\HtmlString;

class FormBuilder extends \Collective\Html\FormBuilder
{
    protected $__formDisabled = false;
    protected $__existsMethodFieldName;
    protected $__existsMethodFieldRequired;

    public function getFieldName($name)
    {
        if (!$this->model) return null;

        if (is_null($this->__existsMethodFieldName)) {
            $this->__existsMethodFieldName = method_exists($this->model, 'getFieldName');
        }

        if ($this->__existsMethodFieldName) {
            return $this->model->getFieldName($name);
        }

        return null;
    }

    public function isFieldRequired($name)
    {
        if (!$this->model) return null;

        if (is_null($this->__existsMethodFieldRequired)) {
            $this->__existsMethodFieldRequired = method_exists($this->model, 'isFieldRequired');
        }

        if ($this->__existsMethodFieldRequired) {
            return $this->model->isFieldRequired($name);
        }

        return null;
    }

    public function label($name, $value = null, $options = [], $escape_html = false)
    {
        if (!$value && $this->model) {
            $value = $this->getFieldName($name).($this->isFieldRequired($name) ? '<span class="required">*</span>' : '');
        }

        return parent::label($name, $value, $options, $escape_html);
    }

    public function text($name, $value = null, $options = [])
    {
        if ($this->model) {
            if (!isset($options['placeholder'])) {
                $options['placeholder'] = $this->getFieldName($name);
            }
        }

        return parent::text($name, $value, $options);
    }

    public function textarea($name, $value = null, $options = [])
    {
        if ($this->model) {
            if (!isset($options['placeholder'])) {
                $options['placeholder'] = $this->getFieldName($name);
            }
        }

        if ($this->__formDisabled) {
            $options[] = 'disabled';
        }

        return parent::textarea($name, $value, $options);
    }

    public function number($name, $value = null, $options = [])
    {
        if (!isset($options['lang'])) {
            $options['lang'] = 'ru';
        }

        if (!isset($options['step'])) {
            $options['step'] = 'any';
        }

        if ($this->model) {
            if (!isset($options['placeholder'])) {
                $options['placeholder'] = $this->getFieldName($name);
            }
        }

        return parent::number($name, $value, $options);
    }

    public function datepicker($name, $value = null, $options = [])
    {
        if (!$value && $this->model) {
            $value = $this->model->{$name};
        }

        if ($this->model) {
            if (!isset($options['placeholder'])) {
                $options['placeholder'] = $this->getFieldName($name);
            }
        }

        if ($this->__formDisabled) {
            $options[] = 'disabled';
        }

        return FormFacade::datepickerInput($name, $value, $options);
    }

    public function timepicker($name, $value = null, $options = [])
    {
        if (!$value && $this->model) {
            $value = $this->model->{$name};
        }

        if ($this->model) {
            if (!isset($options['placeholder'])) {
                $options['placeholder'] = $this->getFieldName($name);
            }
        }

        if ($this->__formDisabled) {
            $options[] = 'disabled';
        }

        return FormFacade::timepickerInput($name, $value, $options);
    }

    public function select2($name, $select, $value = null, $options = [])
    {
        if (!$value && $this->model) {
            $value = $this->model->{$name};
        }

        if ($this->__formDisabled) {
            $options[] = 'disabled';
        }

        return FormFacade::pluginSelect2($name, $select, $value, $options);
    }

    public function checkboxCustom($name, $checked = null, $value = 1, $options = [])
    {
        if (is_null($checked) && $this->model) {
            $checked = $value == $this->model->{$name};
        }

        if (is_string($options)) {
            $options = [
                'label' => $options,
            ];
        } elseif (!isset($options['label']) && $this->model) {
            $options['label'] = $this->getFieldName($name);
        }

        return FormFacade::checkboxStyled($name, $value, $checked, $options);
    }

    public function model($model, array $options = [])
    {
        if (isset($options['disabled']) || in_array('disabled',$options,1)) {
            $this->__formDisabled = true;
        }

        if (isset($options['is_catalog']) || in_array('is_catalog',$options,1)) {
            $this->__formDisabled = !Role::catalog();
        }

        if (isset($options['is_finance']) || in_array('is_finance',$options,1)) {
            $this->__formDisabled = !Role::finance(1);
        }

        if (isset($options['role'])) {
            $this->__formDisabled = !acl($options['role']);
        }

        return parent::model($model,$options);
    }

    public function open(array $options = [])
    {
        if (isset($options['disabled']) || in_array('disabled',$options,1)) {
            $this->__formDisabled = true;
        }

        if (isset($options['is_catalog']) || in_array('is_catalog',$options,1)) {
            $this->__formDisabled = !Role::catalog();
        }

        if (isset($options['is_finance']) || in_array('is_finance',$options,1)) {
            $this->__formDisabled = !Role::finance(1);
        }

        if (isset($options['role'])) {
            $this->__formDisabled = !acl($options['role']);
        }

        return parent::open($options);
    }

    public function select($name, $list = [], $selected = null, $options = [])
    {
        if ($this->__formDisabled) {
            $options[] = 'disabled';
        }

        return parent::select($name, $list, $selected, $options);
    }

    public function submit($value = null, $options = array())
    {
        if ($this->__formDisabled) {
            $options[] = 'disabled';
        }

        return parent::submit($value, $options);
    }

    public function input($type, $name, $value = null, $options = [])
    {
        if ($this->__formDisabled) {
            $options[] = 'disabled';
        }

        return parent::input($type, $name, $value, $options);
    }

    public function inputGroup($elements,$options = [])
    {
        if ($this->__formDisabled) {
            $options[] = 'disabled';
        }

        $html = [];

        foreach($elements as $el) {
            if ($el instanceof HtmlString) {
                $html[] = $el;
            } else {
                $html[] = '<span class="input_group__addon">' . $el . '</span>';
            }
        }

        return '<div class="input_group ' . (isset($options['disabled']) || in_array('disabled',$options,1)
            ? 'input_group-disabled' : '') . '">' .
            implode("",$html) .
        '</div>';
    }
}
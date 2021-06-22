<?php
namespace App\VendorExt\Form;
use Illuminate\Support\HtmlString;

/**
 * Класс расширяющий стандартный класс форм
 *
 * Class FormFacade
 * @package App\Services
 *
 * @method static inputGroup(...$param)
 * @method static datepicker(...$param)
 * @method static checkboxCustom(...$param)
 * @method static timepicker(...$param)
 * @method static select2(...$param)
 */
class FormFacade extends \Collective\Html\FormFacade
{

    /**
     * Create a text input field.
     *
     * @param  string $name
     * @param  string $value
     * @param  array $options
     * @return string
     */
    public static function dt($name, $value = null, $options = array())
    {
        return parent::input('date', $name, $value, $options);
    }

    /**
     * Create a text input field.
     *
     * @param  string $name
     * @param  string $value
     * @param  array $options
     * @return string
     */
    public static function dtime($name, $value = null, $options = array())
    {
        return parent::input('datetime', $name, $value, $options);
    }

    /**
     * Без данной функции, при отправке данных checkbox не приходит, если у него не высталвенна галочка
     *
     * @param $name
     * @param int $value
     * @param null $checked
     * @param array $options
     * @return string
     */
    public static function checkbox($name, $value = 1, $checked = null, $options = [])
    {
        return (strpos($name,'[]') === false ? static::hidden($name, 0) : '') .
        parent::checkbox($name, $value,  $checked, $options);
    }

    /**
     *
     * @param $name
     * @param int $value
     * @param null $checked
     * @param array $options
     * @return string
     */
    public static function checkboxStyled($name, $value = 1, $checked = null, $options = [])
    {
        $title = null;
        if ($options && is_string($options)) {
            $title = $options;
            $options = [];
        } elseif (isset($options['label'])) {
            $title = $options['label'];
            unset($options['label']);
        }

        return
            '<label class="checkboxes__item">' .
                static::checkbox($name, $value, $checked, $options) .
                '<i></i>' .
                ($title ? '<b>' . $title . '</b>' : '') .
            '</label>'
            ;
    }

    public static function ckeditor($name, $value = '', $options = [])
    {
        if (!isset($options['id'])) {
            $options['id'] = 'ckeditor_form__' . $name;
        }

        addJS('lib/ckeditor/ckeditor.js');
        addEventJS("CKEDITOR.replace('" . $options['id'] . "', {
                fullPage : true,
                filebrowserUploadUrl : '" . route('ckeditor_upload_image') . "',
                skin:'office2013',
                extraPlugins : 'afg,showborders,html5validation',
                ShowTableBorders: true,
                removePlugins : 'about',
                allowedContent:true
            });
        ");

        return static::textarea($name, $value, $options);
    }

    public static function datepickerForID($id)
    {
        $js = "
            var picker = new Pikaday({
                field: document.getElementById('" . $id . "'),
                format: 'DD.MM.YYYY',
                yearRange: 100,
                firstDay: 1,
                i18n: {
                    previousMonth : 'Предыдущий месяц',
                    nextMonth     : 'Следующий месяц',
                    months        : ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
                    weekdays      : ['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота'],
                    weekdaysShort : ['Вс','Пн','Вт','Ср','Чт','Пт','Сб']
                },
            });
        ";

        if (\Request::ajax()) {
            echo htmlCSS('lib/pikaday.css');
            echo htmlJS('lib/pikaday.js');
            echo '<script>
                setTimeout(function(){
                    ' . $js . '
                },500);
            </script>';
        } else {
            addCSS('lib/pikaday.css');
            addJS('lib/pikaday.js');
            //addJS('lib/moment/moment.min.js');

            addEventJS("
            (function(){
                " . $js . "
            })();
        ");
        }

    }

    public static function datepickerInput($name, $value = '', $options = [])
    {
        if (!isset($options['id'])) {
            $options['id'] = 'datepicker_form__' . $name;
        }

        if ($value && strpos($value,'-') !== false) {
            $value = date("d.m.Y", strtotime($value));
        }

        static::datepickerForID($options['id']);

        $html = [];
        $html[] = '<div class="input_group">';
        $html[] = static::text($name, $value, $options);
        $html[] = '<a href="#" class="input_group__addon" onclick="document.getElementById(\'' . $options['id'] . '\').click(); return false;">';
        $html[] = '<i class="fa fa-calendar"></i>';
        $html[] = '</a>';
        $html[] = '</div>';

        return implode('',$html);
    }

    public static function timepickerForID($id)
    {
        if (\Request::ajax()) {
            echo htmlJS('lib/jquery.timepicker.js');
            echo htmlCSS('lib/timepicker.css');
            echo '<script>
                setTimeout(function(){
                    $(\'#' . $id . '\').timepicker({
                        timeFormat: \'HH:mm\',
                        interval: 60,
                        startHour: 9
                    });
                },500);
            </script>';

        } else {
            addJS('lib/jquery.timepicker.js');
            addCSS('lib/timepicker.css');

            addEventJS("
            (function(){
                $('#" . $id . "').timepicker({
                    timeFormat: 'HH:mm',
                    interval: 60,
                    startHour: 9
                });
            })();
        ");
        }

    }

    public static function timepickerInput($name, $value = null, $options = [])
    {
        if (!isset($options['id'])) {
            $options['id'] = 'timepicker_form__' . $name;
        }

        if ($value) {
            $value = date("H:i", strtotime($value));
        }

        if (!isset($options['disabled']) && !in_array('disabled',$options)) {
            static::timepickerForID($options['id']);
        }


        $html = [];
        $html[] = '<div class="input_group ' . (isset($options['disabled']) || in_array('disabled',$options) ?
                'input_group-disabled' : '') . '">';
        $html[] = parent::text($name, $value, $options);
        $html[] = '<a href="#" class="input_group__addon" onclick="
        setTimeout(function(){$(\'#' . $options['id'] . '\').timepicker().open();},100);
         return false;
        ">';

        $html[] = '<i class="fa fa-clock-o"></i>';
        $html[] = '</a>';
        $html[] = '</div>';

        return implode('',$html);
    }

    public static function chosen($name, $select, $value = null, $options = [])
    {
        if (!isset($options['id'])) {
            $options['id'] = 'chosen_form__' . $name;
        }

        if (!isset($options['data-placeholder'])) {
            if (isset($options['placeholder'])) {
                $options['data-placeholder'] = $options['placeholder'];
            } else {
                $options['data-placeholder'] = 'Выберите из списка';
            }
        }

        if (isset($options['multiple']) || in_array('multiple', $options)) {
            $name = $name . '[]';
        }

        // Баг со скролом. Добавил chosen.custom.min.js
//        In the not minified js plugin file find following code
//        high_top = this.result_highlight.position().top + this.search_results.scrollTop();
//        and replace it by this.
//        high_top = this.result_highlight.position().top;


        addJS('lib/chosen.custom.min.js');
        addCSS('lib/chosen.css');
        addEventJS("
            $('#" . str_replace('\\', '\\\\', quotemeta($options['id'])) . "').chosen({
                no_results_text: \"По вашему запросу ничего не найдено\",
                allow_single_deselect: true,
                width: \"100%\",
                include_group_label_in_selected:true,
                disable_search_threshold: 10
            });
        ");

        if (isset($options['class'])) {
            $options['class'] .= ' js-chosen-select';
        } else {
            $options['class'] = 'js-chosen-select';
        }

        return static::select($name, $select, $value, $options);
    }

    public static function pluginSelect2($name, $select, $value = null, $options = [])
    {
        if (!isset($options['id'])) {
            $options['id'] = 'select_form__' . $name;
        }

        if (!isset($options['data-placeholder'])) {
            if (isset($options['placeholder'])) {
                $options['data-placeholder'] = $options['placeholder'];
            } else {
                $options['data-placeholder'] = 'Выберите из списка';
            }
        }

        if (isset($options['multiple']) || in_array('multiple', $options)) {
            $name = $name . '[]';
        }

        $searchable = false;
        if (isset($options['searchable']) || in_array('searchable', $options)) {
            $searchable = true;
            unset($options['searchable']);
        }

        $templateResult = null;
        if (isset($options['templateResult'])) {
            $templateResult = $options['templateResult'];
        }

        $templateSelection = $templateResult;
        if (isset($options['templateSelection'])) {
            $templateSelection = $options['templateSelection'];
        }

        $moreParams = '';
        if (isset($options['moreParams'])) {
            $moreParams= $options['moreParams'];
        }

        addJS('lib/select2.min.js');
        addJS('lib/select2/ru.js');
        addCSS('lib/select2.css');
        addEventJS("
            $('#" . str_replace('\\', '\\\\', quotemeta($options['id'])) . "').select2({
                $moreParams
                width: \"100%\"" .
                (!$searchable ? ",minimumResultsForSearch: Infinity" : '') .
                ",templateResult: function (state) {
                    if (!state.id) {
                        return state.text;
                    }

                    var templateResultCallback = " . ($templateResult ? $templateResult : 'null') . ";

                    var _tpl;
                    if (templateResultCallback) {
                        _tpl = templateResultCallback.call(null,state);
                    } else {
                        _tpl = $('<div class=\"select-item\"><div class=\"select-text\">'+state.text+'</div></div>')
                    }

                    return _tpl;
                }" .
                ",templateSelection: function (state) {
                    if (!state.id) {
                        return state.text;
                    }

                    var templateSelectionCallback = " . ($templateSelection ? $templateSelection : 'null') . ";

                    var _tpl;
                    if (templateSelectionCallback) {
                        _tpl = templateSelectionCallback.call(null,state);
                    } else {
                        _tpl = $('<div class=\"select-item\"><div class=\"select-text\">'+state.text+'</div></div>')
                    }

                    return _tpl;
                }" .
            "});
        ");

        return static::select($name, $select, $value, $options);
    }

    public static function upload($name, $files = null, $options = [])
    {

        $label = null;
        if (isset($options['label'])) {
            $label = $options['label'];
            unset($options['label']);
        }
        if (!$label) {
            if (isset($options['multiple'])) {
                $label = 'Выбрать файлы...';
            } else {
                $label = 'Выбрать файл...';
            }
        }

        if (isset($options['class'])) {
            $class = $options['class'];
            unset($options['class']);
        } else {
            $class = '';
        }

        if (isset($options['disabled'])) {
            $disabled = $options['disabled'];
        } else {
            $disabled = false;
        }

        if (isset($options['custom'])) {
            $custom = $options['custom'];
        } else {
            $custom = false;
        }

        addJS('lib/jquery.ui.widget.js');
        addJS('lib/jquery.iframe-transport.js');
        addJS('lib/jquery.fileupload.js');
        addEventJS("
        $(document).off('click', 'li.file a[data-type]');
            $(document).off('click', 'li.file button.delete');
            $(document).on('click', 'li.file a[data-type=file]', function (e) {
                e.preventDefault(); var item = $(this);
                window.open('/storage/file/'+item.data('id')+'?type='+item.data('type'));
            });
        ");

        if (!$disabled && !$custom) {


            addEventJS("
            $(document).on('click', 'li.file button.delete', function (e) {
                e.preventDefault();
                rmEl($(this).siblings('a[data-type]'));
            });

            $('input[name=\"" . $name . "_upload[]\"]').fileupload({
                dataType: 'json',
                url: '" . route('storage_temp') . "',
            formData: { _token: '" . csrf_token() . "' },
            autoUpload: true,
            send: function(e, data) {
                var inputName = data.fileInput[0].name.split('_')[0],
                    fileName = data.files[0].name;
                mkEl(inputName, fileName, function(el, add) {
                    if (add) $('.files_list#'+inputName).append(el);
                    data.context = el;
                    data.context.addClass('progress');
                });
            },
            progress: function(e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                data.context.css({
                    'background': 'linear-gradient(to right, ' +
                    '#c33 0%, #c33 '+progress+'%, ' +
                    '#666 '+progress+'%, #666 100%)'
                });
            },
            done: function (e, data) {
             
                data.context.removeAttr('style').removeClass('progress');
                chEl(data.context.find('a[data-type]'), data.result);
                var fileForm = $('#fileForm');
                if(fileForm) {
                 fileForm.submit();
                 }
               
            }
        })
        ");
        }

        $attachedFiles = [];
        if ($files) {
            $imageFormats = ['png', 'jpg', 'jpeg', 'webp'];

            foreach ($files as $file) {
                $f = '<li class="file">'.
                    ((!$disabled) ? '<button class="delete">&times;</button>' : '');

                $exts = explode('.', $file->filename);
                $extension = strtolower(array_pop($exts));
                if (in_array($extension, $imageFormats) && $disabled) {
                    $f .= '<a href="/storage/file/'.$file->id.'?action=preview" target="' . $file->id . '">'.
                        '<img src="/storage/file/'. $file->id .'" style="max-height: 40px;margin: .9em 1.2em .8em 1.3em;"/>';
                } else if ($extension == 'pdf') {
                    $f .= '<a href="/storage/file/'.$file->id.'?action=preview" target="'. $file->id . '">'.
                        '<img src="/images/file-icon.png" style="max-height: 40px; margin: .9em 1.2em .8em 1.3em;"/>';
                } else {
                    $f .= '<a href="#"  data-id="'.$file->id.'" data-name="'.$file->filename.'" data-input="'.$name.'" data-type="file">'.
                        '<img src="/images/file-icon.png" style="max-height: 40px; margin: .9em 1.2em .8em 1.3em;"/>';
                }
                
                if (!in_array($extension, $imageFormats)) {
                    $f .= '<span class="name">' . $file->filename . '</span>' .
                        '<span class="size">' . format_filesize($file->size) . '</span>';
                }
                $f .= '</a>' .
                    '</li>';

                $attachedFiles[] = $f;
            }
        }
        if (!$custom) {
            $list = '<ul id='.$name.' class="files_list">'.implode('', $attachedFiles).'</ul>';
        } else {
            $list = '';
        }
        $button = '';
        if (!$disabled) {
            static::hidden($name);
            $button = '<label class="btn btn-file '.$class.'" style="margin-top:0;">' .
                '<span>'.($label ?: '').'</span>'.
                static::hidden($name).
                static::file($name.'_upload[]', $options).
                '</label>';
        }

        return $button.$list;
    }

    public static function slider($names = [], $borders = [], $currentValues = [], $options = [])
    {
        if (!isset($options['id'])) {
            $options['id'] = 'select_form__' . implode('_',$names);
        }

        $html = [];

        $html[] = '<div id="' . $options['id'] . '"></div>';

        $html[] = static::hidden($names[0], isset($currentValues[0]) ? $currentValues[0] : null, [
            'id' => $options['id'] . '__' . $names[0]
        ]);

        $html[] = static::hidden($names[1], isset($currentValues[1]) ? $currentValues[1] : null, [
            'id' => $options['id'] . '__' . $names[1]
        ]);


        addCSS('lib/ion.range_slider.css');
        addJS('lib/ion.rangeSlider.min.js');

        addEventJS('
            $("#' . $options['id'] . '").ionRangeSlider({
                type: "double",
                prettify_enabled: false,
                grid: true,
                min: ' . $borders[0] . ',
                max: ' . $borders[1] . ',
                from: ' . (isset($currentValues[0]) ? $currentValues[0] : 'null') . ',
                to: ' . (isset($currentValues[1]) ? $currentValues[1] : 'null') . ',
                step: ' . (isset($options['step']) ? $options['step'] : 1) . ',
                onFinish: function (data) {
                    $("#' . ($options['id'] . '__' . $names[0]) . '").val(data.from);
                    $("#' . ($options['id'] . '__' . $names[1]) . '").val(data.to);
                },
            });
        ');

        return implode("\n",$html);
    }
}

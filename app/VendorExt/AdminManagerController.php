<?php

namespace App\VendorExt;

use App\Services\Admin;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

use \Validator;
use \Illuminate\Http\Request;
use \Redirect;

class AdminManagerController extends BaseController
{
    use DispatchesJobs, ValidatesRequests;

    protected $__dirView = '';
    protected $__routeName = '';
    protected $__modelName = '';
    protected $__topMenuAdmin = [

    ];

    /**
     * @var Model
     */
    protected $__model;

    protected function __initTopMenuAdmin($id, $title = false)
    {
        if (!$this->__topMenuAdmin) return;

        Admin::initMenuTop($this->__topMenuAdmin, $title, $id);
    }

    protected function __getModel()
    {
        if (!$this->__model) {
            $this->__model = new $this->__modelName;
        }

        return $this->__model;
    }

    /**
     * Добавляет ID для валиадции по уникальному ключу
     *
     * @param Model $object
     * @param array $rules
     */
    protected function __updRulesUnique($object, &$rules = [])
    {
        foreach ($rules as $k => &$v) {
            $v_arr = explode('|', $v);

            foreach ($v_arr as &$__rule) {
                if (strpos($__rule, 'unique') !== false) {
                    $__rule .= ',' . $object->id;
                }
            }
            unset($__rule);

            $v = implode('|', $v_arr);
        }
        unset($v);
    }

    protected function __updRules($object, &$rules = [])
    {

    }

    protected function __addSave($object, Request $request)
    {

    }

    protected function __editSave($object, Request $request)
    {

    }

    protected function __editPrepare($object, &$data = [])
    {

    }

    protected function __addPrepare($object, &$data = [])
    {

    }


    public function allAdmin()
    {
        $model = $this->__getModel();

        $items = $model::orderBy('name', 'asc')->paginate(30);

        $data = [
            'items' => $items
        ];

        return adminview($this->__dirView . '.all', $data);
    }

    public function addAdmin(Request $request)
    {
        $item = $this->__getModel();

        $data = [];

        $this->__addPrepare($item, $data);

        if ($request->isMethod('post')) {
            $rules = $item::$rules;

            //$this->__updRulesUnique($item,$rules);
            $this->__updRules($item, $rules);

            $v = Validator::make($request->all(), $rules);
            $v->setAttributeNames($item::$fieldName);

            if ($v->fails()) {
                return redirect()->back()->withInput($request->all())->withErrors($v->errors());
            } else {
                $item->fill($request->except('_token'))->save();
                $item->id;

                $this->__addSave($item, $request);

                return Redirect::route('adm_' . $this->__routeName . '_all');
            }
        }

        $data['item'] = $item;
        $data['listform'] = $item::$fieldName;


        return adminview($this->__dirView . '.add', $data);

    }

    public function editAdmin($id, Request $request)
    {
        $model = $this->__getModel();

        if (!$item = $model::find($id)) {
            abort(404);
        }

        $data = [];

        $this->__editPrepare($item, $data);

        if ($request->isMethod('post')) {
            $rules = $item::$rules;

            $this->__updRulesUnique($item, $rules);
            $this->__updRules($item, $rules);

            $v = Validator::make($request->all(), $rules);
            $v->setAttributeNames($item::$fieldName);

            if ($v->fails()) {
                return redirect()->back()->withInput($request->all())->withErrors($v->errors());
            } else {

                $item->fill($request->input())->save();

                $this->__editSave($item, $request);

                return redirect()->back()->with('success', 1);
            }
        }

        $data['item'] = $item;
        $data['listform'] = $item::$fieldName;

        $this->__initTopMenuAdmin($item->id, $item->name);

        return adminview($this->__dirView . '.edit', $data);
    }

    public function removeAdmin($id, Request $request)
    {
        $model = $this->__getModel();

        if (!$obj = $model::find($id)) abort(404);
        if ($request->isMethod('delete')) $obj->delete();
        return Redirect::route('adm_' . $this->__routeName . '_all');
    }
}

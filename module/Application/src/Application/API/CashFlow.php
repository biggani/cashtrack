<?php
/**
 * Created by JetBrains PhpStorm.
 * User: logo
 * Date: 11/10/13
 * Time: 8:52 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Application\API;


use Refactoring\Interval\SpecificMonth;
use Refactoring\Interval\ThisMonth;
use Zend\Db\Adapter\Adapter;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

class CashFlow extends AbstractRestfulController
{
    public function get($id)
    {
        return new JsonModel(array("type" => 'expense', 'amount' => '30', 'name'=>'ceapa'));
    }

    public function getList()
    {
        $month =  $this->params()->fromQuery('month' , null );
        $list = $this->getJson($month);

        return new JsonModel($list);
    }


    private function getJson($month)
    {



        if ($month == null) {
            $interval = new ThisMonth();
        } else {
            $interval = new SpecificMonth(new \DateTime($month));
        }

        $cashflow = $this->getServiceLocator()->get('Report\CashFlow');

        return $cashflow->forInterval($interval);


    }



}
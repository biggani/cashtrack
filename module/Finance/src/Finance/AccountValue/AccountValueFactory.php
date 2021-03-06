<?php
/**
 * 
 * @author Cosmin Dordea <cosmin.dordea@refactoring.ro>
 * Date: 12/1/13
 */

namespace Finance\AccountValue;


use Finance\Account\AccountFactoryAwareInterface;
use Finance\Account\AccountFactoryAwareTrait;
use Finance\Account\AccountRepositoryAwareInterface;
use Finance\Account\AccountRepositoryAwareTrait;
use Refactoring\Interval\IntervalInterface;
use Zend\Db\Adapter\AdapterAwareInterface;
use Zend\Db\Adapter\AdapterAwareTrait;
use Zend\Stdlib\ArrayObject;

/**
 * Class AccountFactory
 * not sure if this is really a factory
 * @package Finance\AccountValue
 */
class AccountValueFactory implements  AdapterAwareInterface, AccountFactoryAwareInterface, AccountRepositoryAwareInterface
{

    use AdapterAwareTrait;

    use AccountFactoryAwareTrait;

    use AccountRepositoryAwareTrait;



    /**
     * Returns the balance for a certain account
     * @param int $account id
     * @param IntervalInterface $interval
     * @return AccountValue
     */
    public function get($account, IntervalInterface $interval)
    {

        $sql        = $this->getSqlFor($account, $interval);
        $statement = $this->getAdapter()->query($sql);
        $result = $statement->execute();
        $current = $result->current();

        $account = $this->getAccountFactory()->fromDatabaseArray($current);

        return new AccountValue($interval, $account, $current['credit'], $current['debit']);

    }

    /**
     * @var array
     */
    private $accountIds = null;

    /**
     * Gets the value for all accounts for a specific interval
     * @param IntervalInterface $interval
     */
    public function forAllAccounts(IntervalInterface $interval)
    {
        $accounts = $this->getAccountRepository()->all();

        if (null === $this->accountIds) {
            //fixme implement entity holder
            $this->accountIds = array();
            foreach ($accounts as $account) {
                $this->accountIds[]= $account['id'];
            }

        }

        return $this->getList($this->accountIds, $interval);
    }

    /**
     * @param $accountIds
     * @param IntervalInterface $interval
     * @return array
     */
    public function getList($accountIds, IntervalInterface $interval)
    {

        $out = new \ArrayObject();

        foreach ($accountIds as $id) {
            $account = $this->get($id, $interval);
            if ($account->getCredit() >0 || $account->getDebit()  ) {
                $out []= $account;
            }
        }


        return $out;
    }

    /**
     * @return \Zend\Db\Adapter\Adapter
     */
    private function getAdapter()
    {
        if (null === $this->adapter) {
            throw new \RuntimeException('Zend Db Adapter not set');
        }

        return $this->adapter;
    }

    private function getSqlFor($account, IntervalInterface $interval)
    {
        $sql =  "SELECT
            (SELECT round(SUM(amount),2)
                from transaction where to_account=%s
                and  transaction_date >= '%s'
                and transaction_date <= '%s')
            AS debit ,
           (SELECT round(SUM(amount),2) from transaction
                WHERE from_account=%s and transaction_date >= '%s' and transaction_date <= '%s')
            as credit,
            (SELECT name from account where id = %s ) as name,
            (SELECT id from account where id = %s ) as id,
            (SELECT type from account where id = %s ) as type
            ";


        return sprintf(
            $sql,
            $account,
            $interval->getStart()->format('Y-m-d'),
            $interval->getEnd()->format('Y-m-d'),
            $account,
            $interval->getStart()->format('Y-m-d'),
            $interval->getEnd()->format('Y-m-d'),
            $account,
            $account,
            $account
        );

    }

}
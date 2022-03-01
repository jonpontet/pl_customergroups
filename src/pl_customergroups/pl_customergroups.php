<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if ( ! defined( '_PS_VERSION_' ) ) {
  exit;
}

use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Adapter\Entity\Group;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;

class Pl_CustomerGroups extends Module {
  private $html = '';

  public function __construct() {
    $this->name              = 'pl_customergroups';
    $this->tab               = 'administration';
    $this->version           = '1.0.0';
    $this->author            = 'PontetLabs';
    $this->need_instance     = 0;
    $this->tab               = 'administration';
    $this->multishop_context = Shop::CONTEXT_ALL;
    $this->bootstrap         = true;

    parent::__construct();

    $this->displayName = $this->trans( 'PL Customer Groups', [], 'Modules.Plcustomergroups.Admin' );
    $this->description = $this->trans( 'Adds the missing groups information to your customer list.', [], 'Modules.Plcustomergroups.Admin' );

    $this->confirmUninstall = $this->trans( 'Are you sure you want to uninstall this module?', [], 'Admin.Notifications.Warning' );

    $this->ps_versions_compliancy = array( 'min' => '1.7.6.0', 'max' => _PS_VERSION_ );
  }

  public function isUsingNewTranslationSystem() {
    return true;
  }

  public function install() {
    if ( ! parent::install() ) {
      return false;
    }

    if ( $this->registerHook( 'actionCustomerGridDefinitionModifier' ) &&
         $this->registerHook( 'actionCustomerGridQueryBuilderModifier' ) &&
         Configuration::updateValue( 'PL_CUSTOMER_GROUP_COL_POS', 'optin' ) ) {
      return true;
    }

    $this->uninstall();

    return false;
  }

  public function enable( $force_all = false ) {
    return parent::enable( $force_all );
  }

  public function uninstall() {
    Configuration::deleteByName( 'PL_CUSTOMER_GROUP_COL_POS' );

    return parent::uninstall();
  }

  public function getContent() {
    $this->html .= $this->display( __FILE__, 'infos.tpl' );

    $this->postProcess();

    $this->html .= $this->getForm();

    return $this->html;
  }

  /**
   * Return config form
   *
   * @return string
   * @throws PrestaShopException
   */
  private function getForm() {

    $fields_form = array(
      'legend' => array(
        'title' => $this->trans( 'Configuration', [], 'Modules.Plcustomergroups.Admin' ),
        'icon'  => 'icon-cogs',
      ),
      'input'  => array(
        array(
          'type'    => 'select',
          'label'   => $this->trans( 'The column after which to display the new column:', [], 'Modules.Plcustomergroups.Admin' ),
          'name'    => 'CUSTOMER_GROUP_COL_POS',
          'options' => array(
            'query' => array(
              array(
                'id'   => 'id_customer',
                'name' => $this->trans( 'ID', [], 'Admin.Global' )
              ),
              array(
                'id'   => 'social_title',
                'name' => $this->trans( 'Social title', [], 'Admin.Global' )
              ),
              array(
                'id'   => 'firstname',
                'name' => $this->trans( 'First name', [], 'Admin.Global' )
              ),
              array(
                'id'   => 'lastname',
                'name' => $this->trans( 'Last name', [], 'Admin.Global' )
              ),
              array(
                'id'   => 'email',
                'name' => $this->trans( 'Email address', [], 'Admin.Global' )
              ),
              array(
                'id'   => 'total_spent',
                'name' => $this->trans( 'Sales', [], 'Admin.Global' )
              ),
              array(
                'id'   => 'active',
                'name' => $this->trans( 'Enabled', [], 'Admin.Global' )
              ),
              array(
                'id'   => 'newsletter',
                'name' => $this->trans( 'Newsletter', [], 'Admin.Global' )
              ),
              array(
                'id'   => 'optin',
                'name' => $this->trans( 'Partner offers', [], 'Admin.Orderscustomers.Feature' )
              ),
              array(
                'id'   => 'date_add',
                'name' => $this->trans( 'Registration', [], 'Admin.Orderscustomers.Feature' )
              ),
              array(
                'id'   => 'connect',
                'name' => $this->trans( 'Last visit', [], 'Admin.Orderscustomers.Feature' )
              ),
            ),
            'id'    => 'id',
            'name'  => 'name',
          )
        )
      ),
      'submit' => array(
        'title' => $this->trans( 'Save', [], 'Admin.Global' ),
      ),
    );

    $helper                = new HelperForm();
    $helper->show_toolbar  = false;
    $helper->table         = $this->table;
    $helper->submit_action = 'submitConfig';
    $helper->currentIndex  = $this->context->link->getAdminLink( 'AdminModules', false ) .
                             '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
    $helper->token         = Tools::getAdminTokenLite( 'AdminModules' );
    $helper->tpl_vars      = array(
      'fields_value' => $this->getFormValues()
    );

    return $helper->generateForm( array( array( 'form' => $fields_form ) ) );
  }

  /**
   * Return default form field values
   *
   * @return array
   */
  private function getFormValues() {
    $values = array();

    $values['CUSTOMER_GROUP_COL_POS'] = Tools::getValue( 'CUSTOMER_GROUP_COL_POS', Configuration::get( 'PL_CUSTOMER_GROUP_COL_POS' ) );

    return $values;
  }

  /**
   * Config form submit handler
   */
  private function postProcess() {
    if ( Tools::isSubmit( 'submitConfig' ) ) {

      if ( Configuration::updateValue( 'PL_CUSTOMER_GROUP_COL_POS', Tools::getValue( 'CUSTOMER_GROUP_COL_POS' ) ) ) {
        $this->html .= $this->displayConfirmation( $this->trans( 'Successful update.', array(), 'Admin.Notifications.Success' ) );
      } else {
        $this->html .= $this->displayError( $this->trans( 'The settings could not be updated.', array(), 'Modules.Plcustomergroups.Admin' ) );
      }
    }
  }

  /**
   * Add a column with filter to the customer admin page to show all the customers groups
   *
   * @param array $params
   */
  public function hookActionCustomerGridDefinitionModifier( array $params ) {
    $definition = $params['definition'];

    // Get all group names
    $groupChoices = [];
    $groups       = Group::getGroups( (int) $this->context->employee->id_lang );
    foreach ( $groups as $group ) {
      $groupChoices[ $group['name'] ] = $group['id_group'];
    }

    // Add group column
    $definition
      ->getColumns()
      ->addAfter(
        Configuration::get( 'PL_CUSTOMER_GROUP_COL_POS' ),
        ( new DataColumn( 'group_name' ) )
          ->setName( $this->trans( 'Group access', [], 'Admin.Orderscustomers.Feature' ) )
          ->setOptions(
            [
              'field' => 'group_name',
            ] )
      );

    // Add group filter
    $definition->getFilters()->add(
      ( new Filter( 'group_name', ChoiceType::class ) )
        ->setTypeOptions( [
          'choices'                   => $groupChoices,
          'expanded'                  => false,
          'multiple'                  => true,
          'required'                  => false,
          'choice_translation_domain' => false,
        ] )
        ->setAssociatedColumn( 'group_name' )
    );
  }

  /**
   * Query logic for the customer groups column
   *
   * @param array $params
   */
  public function hookActionCustomerGridQueryBuilderModifier(
    array $params
  ) {
    $searchQueryBuilder = $params['search_query_builder'];
    $searchCriteria     = $params['search_criteria'];

    // Show all the customers groups
    $sql = ' (SELECT GROUP_CONCAT(grl.name SEPARATOR ", ")
      FROM ' . _DB_PREFIX_ . 'group_lang grl
      LEFT JOIN ' . _DB_PREFIX_ . 'customer_group cgr ON cgr.id_group = grl.id_group
      WHERE cgr.id_customer = c.id_customer
      AND grl.id_lang = :context_lang_id
      ORDER BY grl.name ASC) as group_name';

    $searchQueryBuilder->addSelect( $sql );

    // Filter customers by group Id
    foreach ( $searchCriteria->getFilters() as $filterName => $filterValues ) {
      if ( 'group_name' === $filterName && is_array( $filterValues ) ) {
        $searchQueryBuilder->leftJoin(
          'c',
          '`' . _DB_PREFIX_ . 'customer_group`',
          'cgr',
          'cgr.`id_customer` = c.`id_customer`'
        );
        // WHERE statements are used instead of INNER JOIN otherwise
        // PrestaShop will not return all valid customers records
        $filterValuesShortened = $filterValues;
        $firstFilterValue      = array_shift( $filterValuesShortened );
        $searchQueryBuilder->andWhere( "cgr.id_group = $firstFilterValue" );
        foreach ( $filterValuesShortened as $value ) {
          $searchQueryBuilder->orWhere( "cgr.id_group = $value" );
        }
      }
    }
  }
}

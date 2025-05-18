<?php

namespace Drupal\bikeclub_report\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with list of contacts with multiple memberships.
 *
 * @Block(
 *   id = "multiple_memberships",
 *   admin_label = @Translation("Multiple Memberships")
 * )
 */
class MultipleMemberships extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $db = \Drupal::database();

    // Array of status codes and names for lookup.
    $mem_status = [
      '1' => 'New',
      '2' => 'Current',
      '3' => 'Grace',
      '4' => 'Expired',
      '5' => 'Pending',
      '6' => 'Cancelled',
      '7' => 'Deceased',
    ];
   
    // Get list of contacts with more than one membership records and convert object to array.
    $contact_list = $db->query("SELECT contact_id FROM {civicrm_membership} GROUP BY contact_id HAVING COUNT(contact_id) > 1")
    ->fetchALL();
    $contacts = json_decode(json_encode($contact_list), true);

    $data = [];

		// Retrieve contact info and membership records for each contact with more than one membership record.
		foreach ($contacts as $contact) {

      $contact_id = reset($contact); // Convert to string.

      // Retrieve contact info and convert object to array.
			$contact_obj = $db->query("SELECT id, display_name FROM {civicrm_contact} WHERE id = :contact", [':contact' => $contact_id, ])
			->fetch();
			$contact_info = json_decode(json_encode($contact_obj), true);

			// Retrieve membership records and convert object to array.
			$member_obj = $db->query("SELECT id, join_date, start_date, end_date, status_id FROM {civicrm_membership} WHERE contact_id = :contact", [':contact' => $contact_id, ])
			->fetchAll();
			$memberships = json_decode(json_encode($member_obj), true);

      foreach($memberships as $membership) {
        // Get membership status name from code, then fill data array.
        $status = [ 'status' =>  $mem_status[$membership['status_id']]      ];
    
        $data[] = array_merge($contact_info, $membership, $status);
      }  

      $data[] = array_fill(1,7,""); // add blank line for readability.

    } 

    // Build table
    $title = "<h3>1. Persons with Multiple Membership records</h3>" . count($contacts) . " found<br>";

    // Provide button link to "Fix memberships"
    $button_style = 'background:#fae07c; color:white;margin:8px 16px;border:1px solid #000;border-radius:3px;text-align:center;';
    $button = "<button style= '" . $button_style . "'><a href='/fix-memberships'>Fix memberships</a></button>";
    $hr = "<p><small>Note: This block is not created by Views. It requires sequential queries and is created programmatically.</small></p><p><hr></p>";

    $header = ['Membership ID','Name','Member Since', 'Start Date', 'End Date','Status Code', 'Status'];
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $data,
      '#empty' => t('No content has been found.'),
      '#attributes' => array (
        'class' => ['w3-table-all','w3-small'],
      ),
      '#cache' => array (
        'max-age' => 0,
      ),
    ];
    $tableHTML = \Drupal::service('renderer')->render($build);

    if (count($contacts) > 0 ) {
      $content = $title . $button . $tableHTML . $hr;
    } else {
      $content = $title . $hr;
    }

    return [
      '#type' => '#markup',
      '#markup' => \Drupal\Core\Render\Markup::create($content), 
    ];

  }
}

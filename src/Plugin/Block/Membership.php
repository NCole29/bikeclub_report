<?php

namespace Drupal\bikeclub_report\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with counts of members at year-end.
 *
 * @Block(
 *   id = "member_count",
 *   admin_label = @Translation("Membership counts"),
 *   category = @Translation("Club"),
 * )
 */
class Membership extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $db = \Drupal::database();

    // Get first membership year in database
    $start = $db->query("SELECT MIN(start_date) FROM {civicrm_membership}")->fetch();
    $start = get_mangled_object_vars($start);
    $start = reset($start);
    $year1 = date('Y', strtotime($start)); // first year in database

    $current_year = date("Y"); // current year

    // Process membership contributions.
    $pvars = "id, contact_id, receive_date, total_amount, contribution_status_id";

    // List of contact_ids with completed membership payments.
    $cids = $db->query("SELECT DISTINCT(contact_id) FROM {civicrm_contribution} 
      WHERE financial_type_id = :finId and contribution_status_id = :status
      ORDER BY contact_id", 
      [':finId' => 2, 
      ':status' => 1, 
      ])
      ->fetchALL();

    // Loop over contacts.  
    foreach ($cids as $contact) {
        // Initialize array of membership years.
        $year = $year1;
        $cid = $contact->contact_id;

        while ($year <= $current_year) {
          $members[$year][$cid] = 0; 
          $year++;       
        }

        // Retrieve all completed membership payments.
        $payments = $db->query("SELECT $pvars FROM {civicrm_contribution} 
          WHERE financial_type_id = :finId and contribution_status_id = :status and contact_id = :cid", 
          [':finId' => 2, 
          ':status' => 1, 
          ':cid' => $cid, ])
          ->fetchALL();

        $i = 0; $fill_yr = 0;

        foreach ($payments as $payment) {
          $year = date('Y', strtotime($payment->receive_date));

          if($i == 0) {
            // Process first membership payment.
              $i++;
              $members[$year][$cid] = 1;
              $fill_yr = $year;

              if( $payment->total_amount > 20 and $year < $current_year) {
                $fill_yr = $fill_yr + 1;
                $members[$fill_yr][$cid] = 1;
              }
          } elseif ($i > 0) { 
              // Process membership payments after the first.
              if ($members[$year][$cid] == 0) {
                // Iterate year to one year after the last one filled.
                $fill_yr = $year;

                $members[$fill_yr][$cid] = 1;

                if( $payment->total_amount > 20 and $year < 2023) {
                  $fill_yr = $fill_yr + 1;
                  $members[$fill_yr][$cid] = 1;
                }
              } elseif ($members[$year][$cid] > 0) {
                // Iterate year to one year after the last one filled.
                $fill_yr = $fill_yr + 1;

                $members[$fill_yr][$cid] = 1;

                if( $payment->total_amount > 20 and $year < 2023) {
                  $fill_yr = $fill_yr + 1;
                  $members[$fill_yr][$cid] = 1;
                }
              } 
          }
        }
    }
 
    // Count memberships each year.
    $year = $year1;

    // For years 2016 to last year (data are incomplete prior to 2016).
    for ($year = 2016; $year < $current_year; $year++) {
      $counts[$year]['year'] = $year;
      $counts[$year]['members'] = array_sum($members[$year]); 
    }

    $key_values = array_column($counts, 'year'); 
    array_multisort($key_values, SORT_DESC, $counts);

    // Build table
    $title = "<h3 class='w3-block-title'>Membership by Year</h3>";
    $header = ['Year', 'Members'];
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $counts,
      '#empty' => 'No content has been found.',
      '#attributes' => array (
        'class' => ['number-table','w3-medium','narrow-table'],
      ),
      '#cache' => array (
        'max-age' => 0,
      ),
    ];
    $tableHTML = \Drupal::service('renderer')->renderPlain($build);
    return [
      '#type' => '#markup',
      '#markup' => $title . $tableHTML,
    ];
  }
}

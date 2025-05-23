<?php

/**
 * @file
 * Install admin nodes with blocks placed in layout builder.
 */

use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;

function club_nodes() {

  // Get current time.
  $now = \Drupal::time()->getRequestTime();

  // Get parent menu links.
  $menu_link_parent = \Drupal::entityTypeManager()
    ->getStorage('menu_link_content')
    ->loadByProperties([
      'title' => 'Admin',
      'menu_name' => 'main',
    ]);
  $admin_menu = reset($menu_link_parent);
  $admin_menu = $admin_menu->uuid();

  $menu_link_parent = \Drupal::entityTypeManager()
    ->getStorage('menu_link_content')
    ->loadByProperties([
      'title' => 'Club reports',
      'menu_name' => 'main',
    ]);
  $reports_menu = reset($menu_link_parent);
  $reports_menu = $reports_menu->uuid();

  // Pages [page_title, section_type, blocks [region => block_id]].
  // One-column layout: all blocks are in the same region, which we call 'content'.
  // Two-column layout: blocks are placed in 'first' and 'second' column regions.
  // See src/Plugin/Block/Membership.php for member_count block.
  $pages = [
    ['title' => 'Century Statistics',
     'link' => 'century-statistics',
     'type' => 'layout_onecol',
     'menu_parent' => $reports_menu,
     'blocks' => [
        ['content', 'views_block:chart_participants-century_registrations'],
        ['content', 'views_block:chart_revenue-century_revenue'],
        ['content', 'views_block:chart_revenue-century_table'],]
    ],
    ['title' => 'Event Statistics, pre-2023',
     'link' => 'event-statistics-pre-2023',
     'type' => 'layout_onecol',
     'menu_parent' => $reports_menu,
     'blocks' => [
        ['content', 'views_block:chart_participants-event_registrations'],
        ['content', 'views_block:chart_revenue-event_revenue'],
        ['content', 'views_block:chart_revenue-event_table'],]
    ],
    ['title' => 'Membership Statistics',
     'link' => 'membership-statistics',
     'type' => 'layout_twocol',
     'menu_parent' => $reports_menu,
     'blocks' => [
        ['first', 'views_block:civicrm_contacts-members_by_status'],
        ['first', 'views_block:civicrm_contacts-members_by_type'],
        ['second', 'member_count'],]
    ],
    ['title' => 'CiviCRM Issues',
     'link' => 'civicrm-issues',
     'type' => 'layout_onecol',
     'menu_parent' => $admin_menu,
    ],

  ];

  // Get taxonomy term ID for page_access = Admin
  $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'page_access', 'name' => 'Admin']);
  $adminId = reset($term)->id();

  $weight = 0; // all pages get same weight so they sort alphabetically
  foreach($pages as $page) {
    // Create the page node.
    $node = Node::create([
      'type' => 'page',
      'langcode' => 'en',
      'created' => $now,
      'changed' => $now,
      'uid' => 1,
      'title' => $page['title'],
      'status' => 1,
      'field_page_access' => $adminId,
    ]);

    // Initialize a section.
    $section = new Section($page['type']);

    if (isset($page['blocks'])) {
      // Create block plugins, put block in section component, and append to section.
      foreach($page['blocks'] as $block) {
        $pluginConfiguration = [
          'id' => $block[1],
          'provider' => 'views',
          'label_display' => TRUE,
          'view_mode' => 'default'];
        $component = new SectionComponent(\Drupal::service('uuid')->generate(), $block[0], $pluginConfiguration);
        $section->appendComponent($component);
      }
      // Add section to page, save, and create path alias -PROBLEM WITH LINE 113.
      $node->layout_builder__layout->setValue($section);
    }
    $node->save();

    // See /modules/contrib/pathauto/pathauto.module
    pathauto_entity_insert($node);

    // Create menu link for the node.
    $menu_link = MenuLinkContent::create([
      'menu_name' => 'main',
      'title' => $page['title'],
      'link' => [ 'uri' => 'internal:/' . $page['link'] ],
      'parent' => 'menu_link_content:' . $page['menu_parent'],
      'expanded'  => TRUE,
      'weight' => $weight,
      ]);
    $menu_link->save();
  } 
}


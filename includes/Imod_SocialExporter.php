<?php

class Imod_SocialExporter
{
    public static function export($mode)
    {
        global $wpdb;

        $option_values = get_option('isig:scores', array(
            'ok_score' => 10,
            'bad_score' => 5,
            'good_score' => 15,
        ));

        $where = '';
        switch ($mode) {
            case 0: { // bad
                $ok_score = $option_values['ok_score'];
                $where = "AND $wpdb->postmeta.meta_value < $ok_score";
                break;
            }
            case 1: { // ok
                $ok_score = $option_values['ok_score'];
                $good_score = $option_values['good_score'];
                $where = "AND $wpdb->postmeta.meta_value >= $ok_score AND $wpdb->postmeta.meta_value < $good_score";
                break;
            }
            case 2: { // good
                $good_score = $option_values['good_score'];
                $where = "AND $wpdb->postmeta.meta_value >= $good_score";
                break;
            }
            case 3: { // all
                break;
            }

        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=posts.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Title', 'Score', 'Url'));

        $rows = $wpdb->get_results("SELECT id, post_title, $wpdb->postmeta.meta_value
  FROM $wpdb->posts 
  INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id
WHERE $wpdb->posts.post_type = 'post' 
AND $wpdb->posts.post_status = 'publish'
AND
$wpdb->postmeta.meta_key = 'isig:score'
$where
ORDER BY $wpdb->postmeta.meta_value
", ARRAY_A);

        foreach ($rows as $r) {
            $r['Url'] = get_permalink($r['id']);
            fputcsv($output, $r);
        }

    }
}
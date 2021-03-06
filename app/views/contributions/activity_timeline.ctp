<?php
/**
 * Tatoeba Project, free collaborative creation of multilingual corpuses project
 * Copyright (C) 2009  HO Ngoc Phuong Trang <tranglich@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  Tatoeba
 * @author   HO Ngoc Phuong Trang <tranglich@gmail.com>
 * @license  Affero General Public License
 * @link     http://tatoeba.org
 */

/**
 * Activity timeline for contributions. It displays for each day the number of new
 * sentences.
 *
 * @category Contributions
 * @package  Views
 * @author   HO Ngoc Phuong Trang <tranglich@gmail.com>
 * @license  Affero General Public License
 * @link     http://tatoeba.org
 */

$this->set('title_for_layout', $pages->formatTitle(__("Activity timeline", true)));

$monthName = $date->monthName($month);
$selectedMonth = format(
    __('{month} {year}', true),
    array('month' => $monthName, 'year' => $year)
);
 
$maxWidth = 400;
$maxTotal = 0;

foreach ($stats as $stat) {
    if ($stat['ContributionsStats']['sentences'] > $maxTotal) {
        $maxTotal = $stat['ContributionsStats']['sentences'];
    }
}
?>

<div id="annexe_content">
    <?php 
    echo $this->element(
        'calendar', 
        array(
            'currentYear' => $year,
            'currentMonth' => $month
        )
    ); 
    ?>
</div>

<div id="main_content">
    <div class="module">
    <h2><?php echo $selectedMonth; ?></h2>

    <?php
    echo '<table id="timeline">';

    $currentDate = null;
    $totalSentences = 0;
    $i = 0;

    foreach ($stats as $stat) {

        $numSentences = $stat['ContributionsStats']['sentences'];
        $date = $stat['ContributionsStats']['date'];

        $width = ($numSentences / $maxTotal) * 100;
        $bar = $html->div('logs_stats', null,
            array('style' => 'width:'.$width.'%')
        );

        echo '<tr>';
        echo $html->tag('td', $date, array('class' => 'date'));
        echo $html->tag('td', $numSentences, array('class' => 'number'));
        echo $html->tag('td', $bar, array('class' => 'bar'));
        echo '</tr>';

    }
    echo '</table>';
    ?>
    </div>
</div>

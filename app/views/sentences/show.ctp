<?php
$this->pageTitle = __('Translations for : ',true) . $sentence['Sentence']['text'];
?>

<?php 
// navigation (previous, random, next)
$sentences->displayNavigation($sentence['Sentence']['id']);

echo '<div class="sentences_set">';
	// sentence menu (translate, edit, comment, etc)
	$sentences->displayMenu($sentence['Sentence']['id'], $sentence['Sentence']['lang'], $sentence['Sentence']['correctness'], $specialOptions);

	// sentence and translations
	$sentences->displayGroup($sentence['Sentence'], $sentence['Translation']);
echo '</div>';	
?>
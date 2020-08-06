<?php
// title
echo '<h1>' . View::escape(Page::getName($selected_page)) . '</h1>';

// description 
echo '<div class="description">' . Page::formatDescription($selected_page, $this->vars) . '</div>';

// page info
// echo '<p class="meta">' . __('Posted on') . ' : ' . date('d/m/Y', $selected_page->added_at) . '</p>';
echo '<div class="clear"></div>';
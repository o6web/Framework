<?php
namespace O6\Framework\StaticIncludes;

class Display
{
	/**
	 * Outputs HTML and Javascript needed for a jQuery datepicker element
	 *
	 * @param string $field_name - name of the field
	 * @param string $default_date - optional default date
	 * @param string $field_id - optional field ID (defaults to $field_name)
	 */
	public static function datePicker($field_name, $default_date = '', $field_id = ''): void
	{
		if (!$field_id) {
			$field_id = $field_name;
		}

		if (($default_date) AND ($default_date !== '0000-00-00')) {
			[$year, $month, $day] = explode('-', $default_date);

			if ((int)$month) {
				$default_date_text = (int)$month . '/';
				$default_date_picked = str_pad($month, 2, '0', STR_PAD_LEFT) . '/';
			} else {
				$reset_default_text = 1;
				$default_date_picked = '01/';
			}

			if ((int)$day) {
				$default_date_text .= (int)$day . '/';
				$default_date_picked .= str_pad($day, 2, '0', STR_PAD_LEFT) . '/';
			} else {
				$reset_default_text = 1;
				$default_date_picked .= '01/';
			}

			$default_date_text .= $year;
			$default_date_picked .= $year;
		}

		echo '<input type="text" name="' . $field_name . '" id="' . $field_id . '" value="' . $default_date_text . '" />';
		echo '<script type="text/javascript">';
		echo '$(\'#' . $field_id . '\').datepicker();';
		if ($default_date_picked) {
			echo '$(\'#' . $field_id . '\').datepicker(\'option\', \'defaultDate\', \'' . $default_date_picked . '\');';
		}
		if ($reset_default_text) {
			echo '$(\'#' . $field_id . '\').attr(\'value\', \'' . $default_date_text . '\');';
		}
		echo '</script>';
	}

	/**
	 * Output HTML for a set of links for navigating through the pages of a set of search results
	 *
	 * @param string $file - base file name, including query string if necessary
	 * @param int $current_record - first record number of the current page
	 * @param int $total_records - total number of records
	 * @param int $increment - number of results per page
	 * @param string $named_anchor - named anchor to append to the link
	 */
	public static function pageNumbers($file, $current_record, $total_records, $increment, $named_anchor = ''): void
	{
		if ((false !== strpos($file, '&')) AND (false === strpos($file, '&amp;'))) {
			$file = str_replace('&', '&amp;', $file);
		}

		if ($total_records) {
			if (false !== strpos($file, '?')) {
				$file .= '&amp;';
			} else {
				$file .= '?';
			}

			$use_pages = [];

			$current_record = (int)(floor($current_record / $increment)) * $increment;

			$last_start = (floor(($total_records - 1) / $increment)) * $increment;

			$current_page = ($current_record / $increment) + 1;
			$last_page = ($last_start / $increment) + 1;

			if ($last_page <= 5) {
				// IF FIVE OR FEWER PAGES, LIST ALL
				for ($n = 1; $n <= $last_page; $n++) {
					$use_pages[] = $n;
				}
			} elseif ($current_page <= 2) {
				// IF CURRENTLY ON FIRST OR SECOND PAGE, LIST FIRST FIVE
				for ($n = 1; $n <= 5; $n++) {
					$use_pages[] = $n;
				}
			} elseif (($last_page - 2) > $current_page) {
				// IF NOT ON LAST OR SECOND-TO-LAST PAGE, SHOW FIVE SURROUNDING CURRENT
				for ($n = ($current_page - 2); $n <= ($current_page + 2); $n++) {
					$use_pages[] = $n;
				}
			} else {
				// IF CURRENTLY ON ONE OF LAST FIVE PAGES, LIST LAST FIVE
				for ($n = ($last_page - 4); $n <= $last_page; $n++) {
					$use_pages[] = $n;
				}
			}

			if ((count($use_pages)) > 1) {
				echo '<div class="page_numbers">';
				echo '<p>Pages: (' . $last_page . ')</p>';
				echo '<ul>';

				if (!in_array(1, $use_pages, false)) {
					$first_file = $file;

					if ($first_file[(strlen($first_file) - 1)] === '?') {
						$first_file = substr($first_file, 0, -1);
					}

					if (substr($first_file, -5) === '&amp;') {
						$first_file = substr($first_file, 0, -5);
					}

					if ($first_file === 'index.php') {
						$first_file = './';
					}

					echo '<li><a href="' . $first_file . $named_anchor . '">&laquo; First</a> ...</li>';
				}

				for ($n = 0; $n < (count($use_pages)); $n++) {
					if ($use_pages[$n] === $current_page) {
						echo '<li class="bold">' . $use_pages[$n] . '</li>';
					} else {
						$this_start = ($use_pages[$n] - 1) * $increment;
						if ($this_start) {
							echo '<li><a href="' . $file . 'start=' . $this_start . $named_anchor . '">' . $use_pages[$n] . '</a></li>';
						} else {
							echo '<li><a href="' . str_replace('&', '&amp;', substr(str_replace('&amp;', '&', $file), 0,
									-1)) . $named_anchor . '">' . $use_pages[$n] . '</a></li>';
						}
					}
				}

				if (!in_array($last_page, $use_pages, false)) {
					echo '<li>... <a href="' . $file . 'start=' . $last_start . $named_anchor . '">Last &raquo;</a></li>';
				}

				echo '</ul>';
				echo '</div><br class="clear" />';
			}
		}
	}

	/**
	 * Outputs the jQuery needed to make a form field autocomplete
	 *
	 * @param bool $inline
	 */
	public static function autocompleteJqueryCode($inline = false): void
	{
	if (!$inline) {
		?>
		<script type="text/javascript">
			if (typeof ROOT_URL === 'undefined' || ROOT_URL === null) {
				let ROOT_URL = '/';
			}

			$(document).ready(function () {
				<?php
				}
				?>
				let $autocomplete = $('input.autocomplete');
				$autocomplete.autocomplete({
					source: function (request, response) {
						$.ajax({
							url: ROOT_URL + '_common/ajax/autocomplete.php',
							data: {
								f: $(this.element).attr('data-autocomplete-type'),
								a: $(this.element).attr('data-autocomplete-additional')
							},
							success: function (data) {
								let results = $.ui.autocomplete.filter(data, request.term);
								response(results.slice(0, 10));
							}
						});
					}
				}).dblclick(function () {
					if (!$(this).attr('data-autocomplete-norefresh')) {
						let el = this;
						let spinner_id = ($(el).attr('id') + '_spinner');
						let spinner = $('<div><i class="fas fa-spinner fa-pulse fa-fw"></i></div>').attr('id', spinner_id);
						$(el).after($(spinner));

						$(spinner).css({
							'position': 'absolute',
							'top': ($(el).offset().top + 1),
							'left': ($(el).offset().left + $(el).outerWidth() - 25),
							'height': ($(el).outerHeight() + 'px'),
							'width': '25px',
							'font-size': (($(el).outerHeight() - 4) + 'px'),
							'line-height': ($(el).outerHeight() + 'px')
						});

						$.ajax({
							url: ROOT_URL + '_common/ajax/autocomplete.php',
							data: {
								f: $(el).attr('data-autocomplete-type'),
								a: $(el).attr('data-autocomplete-additional'),
								n: 1
							},
							success: function () {
								$(spinner).remove();
							}
						});
					}
				});

				$autocomplete.not('[data-autocomplete-allownew]').autocomplete('option', 'autoFocus', true);
				<?php
				if (!$inline) {
				?>
			});
		</script>
		<?php
	}
	}

	/**
	 * Outputs the Javascript for initializing a jQuery modal confirmation box
	 *
	 * @param string $title - box title
	 * @param string $confirm_text - confirmation button text
	 * @param string $cancel_text - cancel button text
	 * @param string $class - class on which this should act
	 * @return string
	 */
	public static function modalConfirmationCode(
		$title = 'Are You Sure?',
		$confirm_text = 'Confirm',
		$cancel_text = 'Cancel',
		$class = 'modal_confirm_link'
	): string
	{
		$dialog_name = 'dialog-' . md5(microtime() . $class);

		?>
		<div id="<?= $dialog_name ?>" title="<?= htmlentities($title, ENT_QUOTES) ?>">
			<span id="<?= $dialog_name ?>-text">Are you sure about this?</span>
		</div>

		<script type="text/javascript">
			$(document).ready(function () {
				$('#<?=$dialog_name ?>').dialog({
					autoOpen: false,
					modal: true
				});
			});

			<?php
			if ($class !== false) {
			// BIND THE CLICK IF WE DIDN'T SPECIFICALLY SAY NOT TO
			?>
			$('.<?=$class ?>').click(function (e) {
				e.preventDefault();
				let targetUrl = $(this).attr('href');

				$('#<?=$dialog_name ?>').dialog({
					buttons: {
						'<?=htmlentities($confirm_text, ENT_QUOTES) ?>': function () {
							window.location.href = targetUrl;
						},
						'<?=htmlentities($cancel_text, ENT_QUOTES) ?>': function () {
							$(this).dialog('close');
						}
					}
				});

				$('#<?=$dialog_name ?>-text').html($(this).attr('title'));

				$('#<?=$dialog_name ?>').dialog('open');
			});
			<?php
			}
			?>
		</script>
		<?php
		return $dialog_name;
	}
}

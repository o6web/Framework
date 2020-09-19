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

					if ($first_file{(strlen($first_file) - 1)} === '?') {
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
				var ROOT_URL = '/';
			}

			$(document).ready(function () {
				<?php
				}
				?>
				var $autocomplete = $('input.autocomplete');
				$autocomplete.autocomplete({
					source: function (request, response) {
						$.ajax({
							url: ROOT_URL + '_common/ajax/autocomplete.php',
							data: {
								f: $(this.element).attr('data-autocomplete-type'),
								a: $(this.element).attr('data-autocomplete-additional')
							},
							success: function (data) {
								var results = $.ui.autocomplete.filter(data, request.term);
								response(results.slice(0, 10));
							}
						});
					}
				}).dblclick(function () {
					if (!$(this).attr('data-autocomplete-norefresh')) {
						var el = this;
						var spinner_id = ($(el).attr('id') + '_spinner');
						var spinner = $('<div><i class="fas fa-spinner fa-pulse fa-fw"></i></div>').attr('id', spinner_id);
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
	 * Outputs the HTML form elements and related Javascript for searching for a player's Hockey-Reference ID
	 *
	 * @param string $player_hockeyref
	 */
	public static function playerHockeyRefFormElements($player_hockeyref = ''): void
	{
		?>
		<div id="player_hockeyref_container">
			<label for="player_hockeyref">Hockey-Reference.com ID:</label>
			<input type="text" name="player_hockeyref" id="player_hockeyref" maxlength="11"
				   value="<?= $player_hockeyref ?>"/><input type="button" id="player_hockeyref_search" value="Search"/>
			<p id="player_hockeyref_search_text">&nbsp;</p><br class="clear"/>
		</div>
		<div id="player_hockeyref_search_container">
			<table class="general padded admin">
				<caption>The list of possible matches for this player at Hockey-Reference.com</caption>
				<thead>
				<tr>
					<th scope="col">ID</th>
					<th scope="col">Name</th>
					<th scope="col" class="actions">Actions</th>
				</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>

		<script type="text/javascript">
			$(document).ready(function () {
				var $hockeyref_container = $('div#player_hockeyref_search_container');
				var $hockeyref_id = $('input#player_hockeyref');
				var $hockeyref_search = $('input#player_hockeyref_search');
				var $hockeyref_search_msg = $('p#player_hockeyref_search_text');
				$hockeyref_container.hide();

				if ($hockeyref_id.val().length) {
					$hockeyref_search.attr('disabled', 'disabled');
				} else {
					$hockeyref_search.removeAttr('disabled');
				}

				$hockeyref_id.keyup(function () {
					if ($(this).val().length) {
						$hockeyref_search.attr('disabled', 'disabled');
					} else {
						$hockeyref_search.removeAttr('disabled');
					}
				});

				$hockeyref_search.click(function () {
					var name = $('table.player_name_list tbody tr td.bold').text();
					var $selected = $('select#name_id option:selected');

					if ((!name) && ($selected.text())) {
						var name_parts = $selected.text().split(',');
						name = $.trim(name_parts[1] + ' ' + name_parts[0]);
					} else {
						if (!name) {
							name = $.trim($('input#first_name').val() + ' ' + $('input#last_name').val());
						}
					}

					if (name) {
						$hockeyref_search_msg.text('Searching Hockey-Reference.com for ' + name);

						$.ajax({
							url: ROOT_URL + '_common/ajax/get_player_hockeyref.php',
							async: true,
							data: {name: name},
							success: function (data) {
								var $hockeyref_table_body = $hockeyref_container.find('table tbody');

								$hockeyref_container.hide();
								$hockeyref_table_body.html('');
								$hockeyref_search_msg.html('&nbsp;');

								if (Array.isArray(data)) {
									for (var n = 0; n < data.length; n++) {
										$hockeyref_table_body.append(
											'<tr><th scope="row">' + data[n].player_id + '</th><td><a href="' + data[n].link + '" rel="external">' + data[n].name + '</a></td><td class="actions"><ul><li><a href="#">Select</a></li></ul></td></tr>');
									}

									$hockeyref_table_body.find('tr td.actions a').click(function (e) {
										e.preventDefault();
										$hockeyref_id.val($(this).closest('tr').children('th').text());
										$hockeyref_container.hide();
										$hockeyref_search.attr('disabled', 'disabled');
									});

									stripe_table_rows('div#player_hockeyref_search_container table');
									external_link_targets();
									$hockeyref_container.show();
									$hockeyref_search_msg.html('&nbsp;');
								} else {
									if (data !== '0') {
										$hockeyref_id.val(data);
										$hockeyref_search.attr('disabled', 'disabled');
										$hockeyref_search_msg.html('&nbsp;');
									} else {
										$hockeyref_search_msg.text('No players were found matching ' + name);
										setTimeout(function () {
											$hockeyref_search_msg.html('&nbsp;');
										}, 5000);
									}
								}
							}
						});
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Outputs the HTML form elements and related Javascript for searching for a player's EliteProspects ID
	 *
	 * @param string $player_eliteprospects
	 */
	public static function playerEliteprospectsFormElements($player_eliteprospects = ''): void
	{
		?>
		<div id="player_eliteprospects_container">
			<label for="player_eliteprospects">EliteProspects.com ID:</label>
			<input type="text" name="player_eliteprospects" id="player_eliteprospects" maxlength="11"
				   value="<?= $player_eliteprospects ?>"/><input type="button" id="player_eliteprospects_search"
																 value="Search"/><input type="button"
																						id="player_eliteprospects_load"
																						value="Load Player Data"/>
			<p id="player_eliteprospects_search_text">&nbsp;</p><br class="clear"/>
		</div>
		<div id="player_eliteprospects_search_container">
			<table class="general padded admin">
				<caption>The list of possible matches for this player at EliteProspects.com</caption>
				<thead>
				<tr>
					<th scope="col">ID</th>
					<th scope="col">Name</th>
					<th scope="col" class="actions">Actions</th>
				</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>

		<script type="text/javascript">
			$(document).ready(function () {
				var $ep_container = $('div#player_eliteprospects_search_container');
				var $ep_id = $('input#player_eliteprospects');
				var $ep_search = $('input#player_eliteprospects_search');
				var $ep_search_msg = $('p#player_eliteprospects_search_text');
				var $ep_load = $('input#player_eliteprospects_load');
				var $birthdate = $('input#birthdate');

				$ep_container.hide();

				if (($ep_id.val().length) && ($ep_id.val() !== '0')) {
					$ep_search.attr('disabled', 'disabled');
				} else {
					$ep_search.removeAttr('disabled');
				}

				if (($ep_id.val().length) && ($ep_id.val() !== '0') && ($birthdate)) {
					$ep_load.show();
				} else {
					$ep_load.hide();
				}

				$ep_id.keyup(function () {
					if (($(this).val().length) && ($(this).val() !== '0')) {
						$ep_search.attr('disabled', 'disabled');
					} else {
						$ep_search.removeAttr('disabled');
					}

					if (($(this).val().length) && ($(this).val() !== '0') && ($birthdate)) {
						$ep_load.show();
					} else {
						$ep_load.hide();
					}
				});

				$ep_search.click(function () {
					var name = $('table.player_name_list tbody tr td.bold').text();
					var $selected = $('select#name_id option:selected');

					if ((!name) && ($selected.text())) {
						var name_parts = $selected.text().split(',');
						name = $.trim(name_parts[1] + ' ' + name_parts[0]);
					} else {
						if (!name) {
							name = $.trim($('input#first_name').val() + ' ' + $('input#last_name').val());
						}
					}

					if (name) {
						$ep_search_msg.text('Searching EliteProspects.com for ' + name);

						$.ajax({
							url: ROOT_URL + '_common/ajax/get_player_eliteprospects.php',
							async: true,
							data: {name: name},
							success: function (data) {
								var $ep_table_body = $ep_container.find('table tbody');

								$ep_container.hide();
								$ep_table_body.html('');
								$ep_search_msg.html('&nbsp;');

								if (Array.isArray(data)) {
									for (var n = 0; n < data.length; n++) {
										$ep_table_body.append(
											'<tr><th scope="row">' + data[n].player_id + '</th><td><a href="' + data[n].link + '" rel="external">' + data[n].name.full_name + '</a></td><td class="actions"><ul><li><a href="#">Select</a></li></ul></td></tr>');
									}

									$ep_table_body.find('tr td.actions a').click(function (e) {
										e.preventDefault();
										$ep_id.val($(this).closest('tr').children('th').text());
										$ep_container.hide();
										$ep_search.attr('disabled', 'disabled');
										if ($birthdate) {
											$ep_load.show();
										}
									});

									stripe_table_rows('div#player_eliteprospects_search_container table');
									external_link_targets();
									$ep_container.show();
									$ep_search_msg.html('&nbsp;');
								} else {
									if (data > 0) {
										$ep_id.val(data);
										$ep_search.attr('disabled', 'disabled');
										if ($birthdate) {
											$ep_load.show();
										}
										$ep_search_msg.html('&nbsp;');
									} else {
										$ep_search_msg.text('No players were found matching ' + name);
										setTimeout(function () {
											$ep_search_msg.html('&nbsp;');
										}, 5000);
									}
								}
							}
						});
					}
				});


				$ep_load.click(function () {
					$ep_search_msg.text('Loading player data from EliteProspects.com');

					$.ajax({
						url: ROOT_URL + '_common/ajax/get_player_eliteprospects.php',
						async: true,
						data: {id: $ep_id.val()},
						success: function (data) {
							$ep_search_msg.html('&nbsp;');
							var parts;

							if ((data.position === 'LW/RW') || (data.position === 'RW/LW')) {
								data.position = 'F';
							}

							var $position = $('select#position');
							if (($position.val() === 'U') && (data.position)) {
								$position.val(data.position)
							}

							var $hand = $('select#hand');
							if (($hand.val() === 'U') && (data.handedness !== '-')) {
								$hand.val(data.handedness)
							}

							var $height = $('select#height');
							if (($height.val() === '0') && (data.height !== '-')) {
								parts = data.height.replace('"', '').split('\'');
								var height = parts[0];
								if (parts[1] < 10) {
									height = height + '.0' + parts[1];
								} else {
									height = height + '.' + parts[1];
								}
								$height.val(height)
							}

							var $weight = $('input#weight');
							if ((($weight.val() === '0') || ($weight.val() === '')) && (data.weight !== '-')) {
								$weight.val(data.weight)
							}

							if (($birthdate.val() === '') && (data.birthdate !== '-')) {
								parts = data.birthdate.split('-');
								if ((parts[1] !== '00') && (parts[2] !== '00')) {
									$birthdate.val(parts[1] + '/' + parts[2] + '/' + parts[0])
								}
							}

							var $city = $('input#city');
							if (($city.val() === '') && (data.birth_city)) {
								$city.val(data.birth_city);
								$('input#province').val(data.birth_state);
								$('input#country').val(data.birth_country);
							}

							var $year = $('input#year');
							if (($year.val() === '') && (data.draft_year)) {
								$year.val(data.draft_year);
								refresh_team_select_options_team_id();
								$('input#round').val(data.draft_round);
								$('input#overall').val(data.draft_overall);
								$('select#team_id option:contains(' + data.draft_team + ')').attr('selected', 'selected');
							}
						}
					});
				});
			});
		</script>
		<?php
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
				var targetUrl = $(this).attr('href');

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

<?php
/** @var $interviews array */
/** @var $myname string */
/** @var $myuser string */
$this->layout('base', ['title' => __('Candidati')]);
$total = 0;
$approved = 0;
$rejected = 0;
$toschedule = 0;
$scheduled = 0;
$invited = 0;
$toinvite = 0;
$later = [];
$prevdate = null;
?>

<nav class="navbar navbar-expand-lg">
	<div class="navbar-brand"><small><?= sprintf(__('Ciao, %s (%s)'), $myname, $myuser) ?></small></div>
	<div class="navbar-nav">
		<a class="nav-item nav-link" href="candidates.php">Candidati</a>
		<a class="nav-item nav-link active" href="interviews.php">Colloqui<span class="sr-only">(current)</span></a>
	</div>
</nav>

<h2><?=__('Colloqui fissati')?></h2>
<table id="interviews" class="table">
	<thead class="thead-dark">
	<tr>
		<th><?=__('Nome')?></th>
		<th><?=__('Interesse')?></th>
		<th><?=__('Ora')?></th>
		<th><?=__('Tenuto da')?></th>
		<th><?=__('Stato')?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach($interviews as $int):
		$total++;
		if($int['when'] === null) {
			$toschedule++;
			$later[] = $int;
			continue;
		}
		$scheduled++;
		$date = $int['when']->format('Y-m-d');
		$time = $int['when']->format('H:i');
		if($int['interviewstatus'] === null) {
			$tobe++;
			$statusCell = "<a href=\"/interviews.php?id=${int['id']}\">" . __('Da decidere') . '</a>';
		} else {
			if($int['interviewstatus'] === true) {
				$approved++;
				$statusCell = $int['invite'] ? __('Colloquio passato, con link d\'invito') : "<a href=\"/interviews.php?id=${int['id']}\">" . __('Colloquio passato') . '</a>';
			} else {
				$rejected++;
				$statusCell = __('Colloquio fallito');
			}
		}
		if($int['questions']) {
			$statusCell .= ' ❓';
		}
		if($int['answers']) {
			$statusCell .= ' ❗️';
		}
		if($int['interviewstatus'] === true) {
			$tdcolor = 'class="table-success"';
		} elseif($int['interviewstatus'] === false) {
			$tdcolor = 'class="table-danger"';
		} else {
			$tdcolor = '';
		}
		if($int['invite']) {
			$invited++;
			$trcolor = $tdcolor;
		} else {
			if($int['interviewstatus'] !== null) {
				$toinvite++;
			}
			$trcolor = '';
		}

		if($date !== $prevdate) {
			$prevdate = $date;
			?>
			<tr class="table-secondary">
				<td colspan="5"><?= sprintf(__('Giorno %s'), $date) ?></td>
			</tr>
			<?php
		}

		?>
		<tr <?= $trcolor ?>>
			<td><a href="/interviews.php?id=<?= $int['id'] ?>"><?= htmlspecialchars($int['name']) ?></a></td>
			<td><?= htmlspecialchars($int['area']) ?></td>
			<td><?= $time ?></td>
			<td><?= htmlspecialchars($int['interviewer']) ?></td>
			<td <?= $tdcolor ?>><?= $statusCell ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<h2><?=__('Colloqui da fissare')?></h2>
<table id="interviews" class="table">
<thead class="thead-dark">
<tr>
	<th><?=__('Nome')?></th>
	<th><?=__('Interesse')?></th>
	<th><?=__('Recruiter che ha approvato')?></th>
</tr>
</thead>
<tbody>
	<?php foreach($later as $int): ?>
		<tr <?= $trcolor ?>>
			<td><a href="/interviews.php?id=<?= $int['id'] ?>"><?= htmlspecialchars($int['name']) ?></a></td>
			<td><?= htmlspecialchars($int['area']) ?></td>
			<td><?= htmlspecialchars($int['recruiter']) ?></td>
		</tr>
	<?php endforeach; ?>
</tbody>
</table>

<!--<ul class="list-group mt-3">-->
<!--	<li class="list-group-item">--><?//= sprintf(_ngettext('%d candidato in totale', '%d candidati totali', $total), $total); ?><!--</li>-->
<!--	<li class="list-group-item list-group-item-primary">--><?//= sprintf(_ngettext('%d da valutare', '%d da valutare', $tobe), $tobe); ?><!--</li>-->
<!--	<li class="list-group-item list-group-item-success">--><?//= sprintf(_ngettext('%d approvato', '%d approvati', $approved), $approved); ?><!--</li>-->
<!--	<li class="list-group-item list-group-item-danger">--><?//= sprintf(_ngettext('%d rifiutato', '%d rifiutati', $rejected), $rejected); ?><!--</li>-->
<!--	<li class="list-group-item">--><?//= sprintf(_ngettext('%d da pubblicare', '%d da pubblicare', $topublish), $topublish); ?><!--</li>-->
<!--	<li class="list-group-item">--><?//= sprintf(_ngettext('%d pubblicato', '%d pubblicati', $published), $published); ?><!--</li>-->
<!--</ul>-->
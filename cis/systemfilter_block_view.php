<?php
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/person.class.php');
require_once('../include/rp_system_filter.class.php');

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('system/filters', null, 's'))
	die($rechte->errormsg);

if (isset($_POST['statistik_kurzbz']))
	$statistik_kurzbz = $_POST['statistik_kurzbz'];
if (isset($_POST['systemfilter_id']))
	$systemfilter_id = $_POST['systemfilter_id'];

$person_id = null;
$person = new person();
if ($person->getPersonFromBenutzer($uid))
{
	$person_id = $person->person_id;
}

$allstatistikfilter = new rp_system_filter();

// alle Systemfilter holen für Dropdownauswahl
$allstatistikfilter->loadAll($statistik_kurzbz, $person_id);

$systemfilter = new rp_system_filter();
$isdefault = $isprivate = false;

if ($systemfilter->load($statistik_kurzbz, $person_id, $systemfilter_id))
{
	if (isset($systemfilter->filter_id) && is_numeric($systemfilter->filter_id))
	{
		if ($systemfilter->default_filter === true)
			$isdefault = true;
		$isprivate = isset($systemfilter->filter_id) &&
			isset($systemfilter->person_id) && is_numeric($systemfilter->person_id)
			&& $systemfilter->person_id === $person_id;
	}
}
?>

<div class="form-inline">
<?php if (is_array($allstatistikfilter->result) && count($allstatistikfilter->result) > 0): ?>
	<div class="row">
	<br><br>
		<div class="col-xs-12">
		<select class="form-control" id ="systemfilter">
			<option value="defaultoption">Ansicht wählen...</option>";
			<?php
			if (isset($allstatistikfilter->result))
			{
				foreach ($allstatistikfilter->result as $sysf)
				{
					$selected = $systemfilter->filter_id === $sysf->filter_id ? " selected='selected'" : "";
					$private = isset($sysf->person_id) ? " (p)" : "";
					echo "<option value = '".$sysf->filter_id."' $selected>".$sysf->getFilterName().$private."</option>";
				}
			}
			?>
		</select>
		<?php if ($isprivate): ?>
		<<?php echo ($isdefault ? "label" : "span");?> id="standardsysfilterlabel">
			<input type="checkbox" name="standardsysfilter" id="standardsysfilter"<?php echo ($isdefault ? " checked='checked'" : "");?>>
			Standard
		<?php echo ($isdefault ? "</label>" : "</span>");?>
		<?php endif; ?>
		</div>
	</div>
<?php endif; ?>
	<br>
	<div class="row">
		<div class="col-xs-8">
			<?php if ($isprivate): ?>
				<button class="btn btn-default" id="updateprivatesysfilterbtn">Ansicht überschreiben</button>
			<?php endif; ?>
			<div class="input-group" id="addprvfiltergroup">
				<input type="text" placeholder="Ansichtname" class="form-control" id="privatesysfiltername">
				<span class="input-group-btn">
					<button class="btn btn-default" id="addprivatesysfilterbtn">Ansicht anlegen</button>
				</span>
			</div>
		</div>
		<?php if ($isprivate): ?>
		<div class="col-xs-1">
			<button class="btn btn-default" id="deleteprivatesysfilterbtn">Ansicht löschen</button>
		</div>
		<?php endif; ?>
	</div>
</div>
<hr>

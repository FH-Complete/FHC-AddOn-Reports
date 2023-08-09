<?php
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/person.class.php');
require_once('../include/rp_system_filter.class.php');

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('addon/reports', null, 's'))
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
$filterresults = $allstatistikfilter->result;

//Get-Filter in Dropdown hinzufügen, falls einer übergeben wird
if (isset($_GET['systemfilter_id']) && $_GET['systemfilter_id'] != '' && $_GET['systemfilter_id'] != 'false')
{
	$getstatistikfilter = new rp_system_filter($statistik_kurzbz,$_GET['systemfilter_id']);
	$filterresults[] = $getstatistikfilter;
}

$systemfilter = new rp_system_filter();
$isdefault = $isprivate = $isadmin = $isglobal = false;
const ORIGINVIEWNAME = 'originview';
$originview = $systemfilter_id === ORIGINVIEWNAME;

if($rechte->isBerechtigt('admin', null, 'suid'))
	$isadmin = true;

if (!$originview)
{
	if ($systemfilter->load($statistik_kurzbz, $person_id, $systemfilter_id))
	{
		if (isset($systemfilter->filter_id) && is_numeric($systemfilter->filter_id))
		{
			if ($systemfilter->default_filter === true)
				$isdefault = true;
			if ($systemfilter->person_id == '')
				$isglobal = true;

			$isprivate = isset($systemfilter->filter_id)
				&& isset($systemfilter->person_id) && is_numeric($systemfilter->person_id)
				&& $systemfilter->person_id === $person_id;
		}
	}
}
?>
<div class="row">
	<div class="col-xs-8" style="padding-top: 10px">
		<div class="panel-group">
			<div class="panel panel-default">
				<div class="panel-heading" id="sysfilterblockheading">
					<a class="accordion-toggle arrowcollapse<?php echo isset($_POST['systemfilter_id']) ? '' : ' collapsed' ?>" data-toggle="collapse" href="#collapseSysFilterHeader">
						<div class="row">
							<div class="col-xs-6">
								<h4 class="panel-title" id="ansichtenverwaltentext">
									Ansichten verwalten
								</h4>
							</div>
							<div class="col-xs-6 text-right">
								<h4 class="panel-title">
									<?php
										$filtername = $systemfilter->getFilterName();
										if ($systemfilter->person_id != '' && $systemfilter->person_id != $person_id)
										{
											$fremdfilterPerson = new person($systemfilter->person_id);
											$filtername .= " (".$fremdfilterPerson->vorname." ".$fremdfilterPerson->nachname."). FILTER NICHT GESPEICHERT!";
										}
										if (!empty($filtername)):
									?>
									<span class="label label-default">Aktive Ansicht: <?php echo $filtername ?></span>
									<?php
										endif;
									?>
								</h4>
							</div>
						</div>
					</a>
				</div>
				<div class="panel-collapse collapse<?php echo (isset($collapseFilterBlock) && $collapseFilterBlock === true ? "" : " in") ?>" id="collapseSysFilterHeader">
					<div class="panel-body form-inline">
					<?php if (is_array($allstatistikfilter->result) && numberOfElements($allstatistikfilter->result) > 0): ?>
						<div class="row">
							<div class="col-xs-12 col-md-7">
							<select class="form-control" id ="systemfilter">
								<option value="originview"<?php echo ($originview ? " selected='selected'" : ""); ?>>
									--Ursprungsansicht--
								</option>";
								<?php
								$filterarray = array();
								if (isset($filterresults))
								{
									foreach ($filterresults as $sysf)
									{
										//Doppelte Filter überspringen
										if (in_array($sysf->filter_id, $filterarray))
											continue;

										$selected = $systemfilter->filter_id === $sysf->filter_id ? " selected='selected'" : "";
										$private = isset($sysf->person_id) ? " (privat)" : "";
										if ($sysf->person_id != '' && $sysf->person_id != $person_id)
										{
											$fremdfilterPerson = new person($sysf->person_id);
											$private =" (".$fremdfilterPerson->vorname." ".$fremdfilterPerson->nachname.") NICHT GESPEICHERT!";
										}
										echo "<option value = '".$sysf->filter_id."' $selected>".$sysf->getFilterName().$private."</option>";

										$filterarray[] = $sysf->filter_id;
									}
								}
								?>
							</select>
							<?php if ($isprivate || $isadmin): ?>
							<<?php echo ($isdefault ? "label" : "span");?> id="standardsysfilterlabel">
								<input type="checkbox" name="standardsysfilter" id="standardsysfilter"<?php echo ($isdefault ? " checked='checked'" : "");?>>
								Standard
							<?php echo ($isdefault ? "</label>" : "</span>");?>
							<?php endif; ?>
							<?php if ($isprivate || $isadmin): ?>
							<<?php echo ($isglobal ? "label" : "span");?> id="globalsysfilterlabel">
								&nbsp;&nbsp;<input type="checkbox" name="globalsysfilter" id="globalsysfilter"<?php echo ($isglobal ? " checked='checked'" : "");?>>
								Global
							<?php echo ($isglobal ? "</label>" : "</span>");?>
							<?php endif; ?>
							</div>
							<?php if ($isprivate || $isadmin): ?>
							<div class="col-xs-12 col-md-5 text-right">
								<button class="btn btn-default" id="updateprivatesysfilterbtn">Ansicht überschreiben</button>
								<a class="btn btn-default" href="mailto:?body=<?php echo CIS_ROOT.'addons/reports/cis/vorschau.php?statistik_kurzbz='.$statistik_kurzbz.'%26debug=true%26systemfilter_id='.$systemfilter->filter_id; ?>" role="button" title="Ansicht per Mail teilen">
									<!-- Teilen-Symbol als SVG-Pfad zeichnen -->
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-share" viewBox="0 0 16 16">
										<path d="M13.5 1a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM11 2.5a2.5 2.5 0 1 1 .603 1.628l-6.718 3.12a2.499 2.499 0 0 1 0 1.504l6.718 3.12a2.5 2.5 0 1 1-.488.876l-6.718-3.12a2.5 2.5 0 1 1 0-3.256l6.718-3.12A2.5 2.5 0 0 1 11 2.5zm-8.5 4a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm11 5.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/>
									</svg>
								</a>
								<div style="display: none">
									<?php echo $systemfilter->filter ?>
								</div>
							</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
						<br>
						<div class="row">
							<div class="col-xs-12 col-md-8">
								<div class="input-group" id="addprvfiltergroup">
									<input type="text" placeholder="Ansichtname" class="form-control" id="privatesysfiltername">
									<span class="input-group-btn">
										<button class="btn btn-default" id="addprivatesysfilterbtn">Ansicht anlegen</button>
									</span>
								</div>
							</div>
							<?php if ($isprivate): ?>
							<div class="col-xs-12 col-md-4 text-right">
								<button class="btn btn-default" id="deleteprivatesysfilterbtn">Ansicht löschen</button>
							</div>
							<?php endif; ?>
						</div>
						<div id="sysfiltermsg" class="text-center"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Spanish language pack for Course agenda
 *
 * @package    report_courseagenda
 * @category   string
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activitytype'] = 'Tipo de actividad';
$string['allactivities'] = 'Todas las actividades';
$string['automaticgrade'] = 'Calificación automática';
$string['completionconditions'] = 'Condiciones para completar esta actividad';
$string['conditionstounlock'] = 'Condiciones para desbloquear esta actividad';
$string['courseagenda:view'] = 'Ver informe Agenda del curso';
$string['courseagenda:viewall'] = 'Ver informe Agenda del curso para todos los usuarios';
$string['coursedurationformat'] = 'Formato de duración del curso';
$string['coursedurationformat_help'] = 'Seleccione el formato para mostrar la duración del curso.';
$string['daystograde'] = 'Días para calificar';
$string['daystograde_help'] = 'La cantidad de días que tiene un docente para calificar la actividad de un estudiante.';
$string['daystosendactivity'] = 'Días para enviar la actividad';
$string['daystosendactivity_help'] = 'El número de días que tiene un estudiante para enviar una actividad antes de que se genere la alerta de envío.';
$string['deadlinedelivery'] = 'Fecha límite para la entrega';
$string['excludemodules'] = 'Excluir módulos';
$string['excludemodules_help'] = 'Seleccione los módulos que desea excluir del informe.';
$string['extensiondate'] = 'La actividad se extendió hasta <span>{$a}</span>';
$string['feedbackdate'] = 'Retroalimentación';
$string['field_academiccredits'] = 'Créditos académicos';
$string['field_academiccredits_help'] = 'Un campo personalizado a usar para los créditos académicos del curso.';
$string['fullstate_active'] = 'Activo';
$string['fullstate_approved'] = 'Actividad aprobada';
$string['fullstate_blocked'] = 'Bloqueado';
$string['fullstate_completed'] = 'Actividad completada';
$string['fullstate_delivered'] = 'Pendiente de respuesta del profesor';
$string['fullstate_failed'] = 'Actividad reprobada';
$string['fullstate_pending'] = 'Pendiente';
$string['fullstate_pendingdays'] = 'Faltan {$a} días';
$string['fullstate_retarded'] = 'Con retraso {$a} días';
$string['fullstate_retardedactive'] = 'Con retraso pero disponible hasta {$a}';
$string['fullstate_undelivered'] = 'Actividad no entregada';
$string['graded'] = 'Calificada';
$string['gradetopass'] = 'Calificación para aprobar';
$string['gradetopass_help'] = 'La calificación mínima que debe obtener un estudiante para aprobar el curso. Se utiliza cuando el módulo no está configurado.';
$string['hoursbycredit'] = 'Horas por crédito';
$string['hoursbycredit_help'] = 'El número de horas que debe dedicar un estudiante para obtener un crédito académico.';
$string['includesection0'] = 'Incluir sección 0';
$string['includesection0_help'] = 'Incluir la sección 0 y sus actividades en el reporte.';
$string['infodate_available_between'] = 'Disponible desde <span>{$a->from} hasta {$a->until}</span>';
$string['infodate_available_from'] = 'Disponible desde <span>{$a}</span>';
$string['infodate_available_on'] = 'Disponible el <span>{$a}</span>';
$string['infodate_available_until'] = 'Disponible hasta el <span>{$a}</span>';
$string['infodate_delivered_between'] = 'Entregada del <span>{$a->from} hasta el {$a->until}</span>';
$string['infodate_delivered_from'] = 'Entregada el <span>{$a}</span>';
$string['infodate_delivered_on'] = 'Entregada en <span>{$a}</span>';
$string['infodate_delivered_until'] = 'Entregada desde <span>{$a}</span>';
$string['infodate_expired_between'] = 'Vencido desde <span>{$a->from} hasta {$a->until}</span>';
$string['infodate_expired_from'] = 'Venció el <span>{$a}</span>';
$string['infodate_expired_on'] = 'Vencido en <span>{$a}</span>';
$string['infodate_expired_until'] = 'Vencido desde <span>{$a}</span>';
$string['noenddate'] = 'Sin fecha de finalización';
$string['notdefined'] = 'No definido';
$string['notuntil'] = 'Sin fecha de finalización';
$string['notweightedactivities'] = 'Actividades sin ponderación';
$string['page-report-courseagenda-index'] = 'Informe Agenda del curso';
$string['page-report-courseagenda-x'] = 'Cualquier informe de Agenda del curso';
$string['pluginname'] = 'Agenda del curso';
$string['privacy:metadata'] = 'El complemento Agenda del curso no almacena ningún dato personal.';
$string['progresscolors'] = 'Colores para el progreso';
$string['progresscolors_help'] = 'Seleccione la lista de colores que desea utilizar en la barra de progreso.
Utilice el formato: <b>color|porcentaje</b> (uno por línea).<br>
Ejemplo:<br>#ff0000|33<br>#ffe600|66<br>#00ff00<br>';
$string['reportsummary'] = 'Hola <span>{$a->userfullname}</span>, este es un curso con una duración de <b>{$a->duration} {$a->studytime}</b>, será guiado por <b>{$a->teacher}</b>. Comenzará el <b>{$a->coursestartdate}</b> y finalizará el <b>{$a->courseenddate}</b>.';
$string['settingsappearanceheader'] = 'Apariencia';
$string['settingsgeneralheader'] = 'Generales';
$string['state_active'] = 'Activa';
$string['state_approved'] = 'Aprobada';
$string['state_blocked'] = 'Bloqueada';
$string['state_completed'] = 'Completada';
$string['state_delivered'] = 'Entregada';
$string['state_failed'] = 'Reprobada';
$string['state_pending'] = 'Pendiente';
$string['state_retarded'] = 'Retrasada';
$string['state_undelivered'] = 'No entregada';
$string['statesoptions'] = 'Opciones de estados';
$string['statesoptions_help'] = 'Seleccione los colores e íconos que se usarán en los estados de los recursos.
Utilice el formato: <b>estado|color|icono</b> (uno por línea).<br>
Para ver la lista de posibles iconos, consulte: <a href="https://componentlibrary.moodle.com/admin/tool/componentlibrary/docspage.php/moodle/components/moodle-icons/" target="_blank">Iconos de Moodle</a><br>
<b>Estados disponibles:</b> active, blocked, pending, completed, approved, failed, delivered, undelivered, retarded.<br>
Ejemplo:<br>failed|#FF0000<br>pending|#ffe600|tool_policy:pending<br>approved|#00FF00|core:t/approve<br>';
$string['studytimelabel'] = '- {$a->dedication} horas de dedicación ({$a->credits} créditos académicos)';
$string['timehoursrange'] = 'desde {$a->from} hasta {$a->until}';
$string['viewactivity'] = 'Ver actividad';
$string['weighing'] = 'ponderado';
$string['weightedactivities'] = 'Actividades ponderadas';

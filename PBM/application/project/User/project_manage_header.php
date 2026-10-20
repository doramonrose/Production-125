<?php
/**
 * แถบหัว: ชื่อโครงการ + ปุ่มแก้ไข (แสดงก่อน) / บันทึก (แสดงหลังกดแก้ไข)
 *
 * กำหนดตัวแปรก่อน include:
 *   $page, $op_from, $years_select, $overview, $edit_project_id (int), $name_project_display (string)
 *
 * ใน project_manage.php ที่มีแถบเดิมอยู่แล้ว: ให้ใส่ในแบบฟอร์มบันทึกชื่อ
 *   <input type="hidden" name="ajax" value="1">
 *   <input type="hidden" name="project_id" value="<?= (int)$edit_project_id ?>">
 * และซ่อนปุ่มบันทึกด้วย class d-none จนกว่าจะกดแก้ไข (หรือแทนที่บล็อกเดิมด้วย include ไฟล์นี้)
 */
if (!isset($page, $op_from, $years_select, $overview, $edit_project_id, $name_project_display)) {
	return;
}
$ep = htmlspecialchars((string)$page, ENT_QUOTES, 'UTF-8');
$eof = htmlspecialchars((string)$op_from, ENT_QUOTES, 'UTF-8');
$ey = htmlspecialchars((string)$years_select, ENT_QUOTES, 'UTF-8');
$eo = htmlspecialchars((string)$overview, ENT_QUOTES, 'UTF-8');
$eid = (int)$edit_project_id;
$ename = htmlspecialchars((string)$name_project_display, ENT_QUOTES, 'UTF-8');
?>
<div class="card border-0 shadow-sm mb-3">
	<div class="card-body py-3">
		<form id="form-project-name-toolbar" class="mb-0" action="?page=<?= $ep; ?>&op=save_project" method="post" autocomplete="off">
			<input type="hidden" name="ajax" value="1">
			<input type="hidden" name="op_from" value="<?= $eof; ?>">
			<input type="hidden" name="years_select" value="<?= $ey; ?>">
			<input type="hidden" name="overview" value="<?= $eo; ?>">
			<input type="hidden" name="project_id" value="<?= $eid; ?>">
			<div class="d-flex flex-wrap align-items-center">
				<div class="d-flex align-items-center mr-2 mb-2 mb-md-0">
					<span class="badge badge-warning text-dark px-3 py-2 mr-2">ร่าง</span>
					<span class="text-110 font-weight-bold text-dark d-none d-sm-inline">โครงการ</span>
				</div>
				<div class="flex-grow-1 mb-2 mb-md-0" style="min-width:200px;">
					<label class="sr-only" for="name_project_header">ชื่อโครงการ</label>
					<input type="text" name="name_project" id="name_project_header" class="form-control border-warning"
						value="<?= $ename; ?>" readonly required>
				</div>
				<div class="d-flex align-items-center ml-md-auto">
					<button type="button" class="btn btn-warning text-dark font-weight-bold px-3 mr-2" id="js-header-edit-project" title="แก้ไขชื่อโครงการ">
						<i class="fa fa-edit mr-1"></i> แก้ไข
					</button>
					<button type="submit" class="btn btn-success font-weight-bold px-3 d-none" id="js-header-save-project" title="บันทึกชื่อโครงการ">
						<i class="fa fa-save mr-1"></i> บันทึก
					</button>
				</div>
			</div>
		</form>
	</div>
</div>
<script>
(function() {
	var $form = $('#form-project-name-toolbar');
	if (!$form.length) return;

	$('#js-header-edit-project').on('click', function() {
		$('#name_project_header').prop('readonly', false).focus();
		$(this).addClass('d-none');
		$('#js-header-save-project').removeClass('d-none');
	});

	$form.on('submit', function(e) {
		e.preventDefault();
		var url = $form.attr('action');
		$.ajax({
			url: url,
			type: 'POST',
			data: $form.serialize(),
			dataType: 'json'
		}).done(function(res) {
			if (res && res.ok === 1) {
				if (typeof Swal !== 'undefined') {
					Swal.fire({ icon: 'success', title: 'บันทึกข้อมูลเรียบร้อย', timer: 1400, showConfirmButton: false })
						.then(function() {
							if (res.redirect) window.location.href = res.redirect;
						});
				} else if (res.redirect) {
					window.location.href = res.redirect;
				}
				$('#name_project_header').prop('readonly', true);
				$('#js-header-save-project').addClass('d-none');
				$('#js-header-edit-project').removeClass('d-none');
			} else {
				var msg = (res && res.msg) ? res.msg : 'ไม่สามารถบันทึกข้อมูลได้';
				if (typeof Swal !== 'undefined') {
					Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: msg, confirmButtonText: 'ตกลง' });
				} else {
					alert(msg);
				}
			}
		}).fail(function(xhr) {
			var msg = 'ไม่สามารถบันทึกข้อมูลได้';
			try {
				var j = JSON.parse(xhr.responseText);
				if (j && j.msg) msg = j.msg;
			} catch (err) { /* response ไม่ใช่ JSON */ }
			if (typeof Swal !== 'undefined') {
				Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: msg, confirmButtonText: 'ตกลง' });
			} else {
				alert(msg);
			}
		});
	});
})();
</script>

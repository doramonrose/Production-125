<?php
// ถูก include จาก page.php ที่ start session แล้ว — ไม่เรียก session_start()
include_once(__DIR__ . '/../../config/config_db.php');
include_once(__DIR__ . '/../fn/main_project.php');

$page = isset($_GET['page']) ? trim((string)$_GET['page']) : '';
$op   = isset($_GET['op']) ? trim((string)$_GET['op']) : '';
?>

	<style>
		/* --- Select2 + Bootstrap5 --- */

		/* กล่องหลักให้เหมือน form-select */
		.select2-container .select2-selection--single {
			height: calc(2.5rem + 2px);
			padding: 0.375rem 0.75rem;
			border: 1px solid #ced4da;
			border-radius: 0.375rem;
			background-color: #fff;
			display: flex;
			align-items: center;
			font-size: 1rem;
		}

		/* ตัวหนังสือ */
		.select2-container--default .select2-selection--single .select2-selection__rendered {
			color: #212529;
			line-height: 1.5;
			padding-left: 0;
		}

		/* ลูกศร dropdown */
		.select2-container--default .select2-selection--single .select2-selection__arrow {
			height: 100%;
			right: 0.75rem;
		}

		/* Hover + Focus */
		.select2-container--default .select2-selection--single:focus,
		.select2-container--default .select2-selection--single:active,
		.select2-container--default .select2-selection--single:hover {
			border-color: #86b7fe;
			outline: 0;
			box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
		}

		/* Dropdown list */
		.select2-container .select2-dropdown {
			border-radius: 0.375rem;
			border: 1px solid #ced4da;
		}

		/* Option ที่ hover */
		.select2-results__option--highlighted {
			background-color: #0d6efd !important;
			color: #fff !important;
		}

		/* Multiple select */
		.select2-container--default .select2-selection--multiple {
			min-height: calc(2.5rem + 2px);
			border: 1px solid #ced4da;
			border-radius: 0.375rem;
			padding: 0.375rem 0.75rem;
			display: flex;
			flex-wrap: wrap;
			align-items: center;
		}

		/* Tag ที่เลือกแล้ว */
		.select2-container--default .select2-selection--multiple .select2-selection__choice {
			background-color: #0d6efd;
			border: none;
			color: #fff;
			border-radius: 0.25rem;
			padding: 0.25rem 0.5rem;
			margin-top: 0.25rem;
		}

		/* ปุ่มลบ tag */
		.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
			color: #fff;
			margin-right: 5px;
		}
	</style>

	<?php function pre_project()
	{
		global $db_Project, $db_person;
		$page = $_GET['page'];
		$op_from = $_GET['op'];
	?>
		<!-- ฟอร์มค้นหา -->
		<div class="row g-3">
			<!-- ปีงบประมาณ -->
			<div class="col-md-3">
				<label class="form-label">ปีงบประมาณ</label>
				<select id="budget_year" class="form-select select2">
					<option value="">- เลือกปี -</option>
					<option value="2568">2568</option>
					<option value="2569">2569</option>
				</select>
			</div>

			<!-- รหัสโครงการ -->
			<div class="col-md-3">
				<label class="form-label">รหัสโครงการ</label>
				<select id="project_code" class="form-select select2">
					<option value="">- เลือกรหัสโครงการ -</option>
				</select>
			</div>

			<!-- ชื่อโครงการ -->
			<div class="col-md-6">
				<label class="form-label">ชื่อโครงการ</label>
				<input type="text" id="project_name" class="form-control" readonly>
			</div>
		</div>

		<!-- ✅ Select2 CDN -->
		<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
		<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

		<script>
			$(document).ready(function() {
				$('.select2').select2({
					placeholder: "เลือก...",
					allowClear: true,
					width: '100%'
				});

				// เมื่อเลือกปีงบประมาณ
				$('#budget_year').on('change', function() {
					let year = $(this).val();

					if (year) {
						$.ajax({
							url: 'get_pre_project.php',
							type: 'GET',
							data: {
								year: year
							},
							dataType: 'json',
							success: function(data) {
								let $project = $('#project_code');
								$project.empty().append('<option value="">- เลือกรหัสโครงการ -</option>');

								$.each(data, function(i, item) {
									$project.append('<option value="' + item.code + '" data-name="' + item.name + '">' + item.code + '</option>');
								});

								$project.val(null).trigger('change');
							}
						});
					}
				});

				// เมื่อเลือกรหัสโครงการ
				$('#project_code').on('change', function() {
					let name = $(this).find(':selected').data('name') || '';
					$('#project_name').val(name);
				});
			});
		</script>

	<?php }  //pre_project
	?>


	<?php
	switch ($op) {
		case "pre_project":
			pre_project();
			break;
		case "set_project_nature":
			set_project_nature();
			break;
		case "set_project_budget":
			set_project_budget();
			break;
		case "set_project_expenses":
			set_project_expenses();
			break;
		case "set_project_status":
			set_project_status();
			break;
		case "set_project_target":
			set_project_target();
			break;

		case "save_data":

			global $db_Project, $db_person;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			if ($op_from == "set_Project_Type") {

				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "INSERT INTO set_project_type(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_project_nature") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "INSERT INTO set_project_nature(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_project_budget") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "INSERT INTO set_project_budget(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_project_expenses") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "INSERT INTO set_project_expenses(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_project_status") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "INSERT INTO set_project_status(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_project_target") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "INSERT INTO set_project_target(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "expenses_detail") {
				$code_expenses = $_POST['id'];
				$name = $_POST['name'];
				$detail = $_POST['detail'];
				$op_from = "set_project_expenses";
				$sql = mysqli_query($db_Project, "INSERT INTO set_project_expenses_detail(code_expenses,name,detail,status)VALUE('$code_expenses','$name','$detail','$status')");
			}



			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from;
				$message = '<script>';
				$message .= 'swal({';
				$message .= 'title: "บันทึกข้อมูลเรียบร้อย .",';
				$message .= 'text: " Success",';
				$message .= 'icon: "success",';
				$message .= '}).then((will) => {
											window.location.assign("';
				$message .= $link;
				$message .= '");});';
				$message .= '</script>';
				echo $message;
			} else {
				$link = "?page=" . $page . '&op=' . $op_from;
				$message = '<script>';
				$message .= 'swal({';
				$message .= 'title: "บันทึกข้อมูลไม่สำเร็จ.",';
				$message .= 'text: " กรุณาลองใหม่อีกครั้งค่ะ.",';
				$message .= 'icon: "error",';
				$message .= '}).then((will) => {
											window.location.assign("';
				$message .= $link;
				$message .= '");});';
				$message .= '</script>';
				echo $message;
			}


			break;
		case "update_data":

			global $db_Project, $db_person;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$op_from = $_POST['op_from'];

			if ($op_from == "set_Project_Type") {

				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "UPDATE set_project_type SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_project_nature") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "UPDATE set_project_nature SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_project_budget") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "UPDATE set_project_budget SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_project_expenses") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "UPDATE set_project_expenses SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_project_status") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "UPDATE set_project_status SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_project_target") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "UPDATE set_project_target SET name='$name' WHERE id='$_POST[id]' ");
			}



			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from;
				$message = '<script>';
				$message .= 'swal({';
				$message .= 'title: "บันทึกการแก้ไขข้อมูลเรียบร้อย .",';
				$message .= 'text: " Success",';
				$message .= 'icon: "success",';
				$message .= '}).then((will) => {
											window.location.assign("';
				$message .= $link;
				$message .= '");});';
				$message .= '</script>';
				echo $message;
			} else {
				$link = "?page=" . $page . '&op=' . $op_from;
				$message = '<script>';
				$message .= 'swal({';
				$message .= 'title: "บันทึกการแก้ไขข้อมูลไม่สำเร็จ.",';
				$message .= 'text: " กรุณาลองใหม่อีกครั้งค่ะ.",';
				$message .= 'icon: "error",';
				$message .= '}).then((will) => {
											window.location.assign("';
				$message .= $link;
				$message .= '");});';
				$message .= '</script>';
				echo $message;
			}


			break;
		case "del_set_all":
			global $db_Project, $db_person;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$op_from = $_POST['op_from'];
			$tb_data = $_POST['op_from'];
			$id = $_GET['id'];
			if ($id != "") {

				$sql = mysqli_query($db_Project,  "UPDATE $tb_data SET status='99' WHERE  id='$id' ");
			}

			if ($sql) {

				$link = "?page=" . $page . '&op=' . $op_from;
				$message = '<script>';
				$message .= 'swal({';
				$message .= 'title: "ลบข้อมูลเรียบร้อย .",';
				$message .= 'text: " Success",';
				$message .= 'icon: "success",';
				$message .= '}).then((will) => {
                                    window.location.assign("';
				$message .= $link;
				$message .= '");});';
				$message .= '</script>';
				echo $message;
			} else {
				$link = "?page=" . $page . '&op=' . $op_from;
				$message = '<script>';
				$message .= 'swal({';
				$message .= 'title: "ลบข้อมูลไม่สำเร็จ.",';
				$message .= 'text: " กรุณาลองใหม่อีกครั้งค่ะ.",';
				$message .= 'icon: "error",';
				$message .= '}).then((will) => {
                                    window.location.assign("';
				$message .= $link;
				$message .= '");});';
				$message .= '</script>';
				echo $message;
			}



			break;
	}
	?>
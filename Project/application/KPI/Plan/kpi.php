<?php
session_start();
include("../../../config/connect_db.php");
include("../fn/main_kpi.php");

$page = $_GET['page'];
$op = $_GET['op'];

?>


<body>

	<?php function kpi()
	{
		include("../../../config/connect_db.php");
		$page = $_GET['page'];
		$op_from = $_GET['op'];
	?>



		<div class="row mt-4">
			<!-- orders table -->
			<div class="col-12 col-lg-12">
				<div class="bcard card h-100 pt-1 pt-lg-2">

					<div class="card-header border-0">
						<h5 class="card-title text-dark-m2 pl-1 text-110">
							<i class="fas fa-list-ul"></i> ฐานข้อมูลตัวชี้วัด
						</h5>
						<div class="card-toolbar align-self-center no-border">
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-mark" role="button" data-toggle="modal">
									<i class="fas fa-plus"></i> เพิ่มข้อมูลตัวชี้วัด
								</a>
							</div>
						</div>

						<div class="card-toolbar align-self-center">
							<a class="btn btn-green text-600 btn-xs mr-1" href="#modal-import-data" role="button" data-toggle="modal">
								<i class="fas fa-file-import"></i> นำเข้าข้อมูลตัวชี้วัด EXCEL File
							</a>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive-md">

							<table id="table_indicator" class="table table-border-x brc-secondary-l3 mb-0">
								<thead>
									<tr class="text-80 text-default-d3 bgc-default-l4">
										<th class="pl-2 pl-lg-4">
											ลำดับที่
										</th>
										<th width="60%">
											ข้อมูลตัวชี้วัด
										</th>

										<th>
											สถานะการใช้งาน
										</th>

										<th>
											จัดการข้อมูล
										</th>
										<th>
											จัดการตัวชี้วัด
										</th>

									</tr>
								</thead>

								<tbody>
									<?php
									$kpi_data = getKPIList($db_KPI);

									if (!empty($kpi_data)) :
										foreach ($kpi_data as $i => $kpi) :


									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>

												<td class="text-dark-m2 text-85">
													<?php echo htmlspecialchars($kpi['name']); ?>
												</td>




												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $kpi['id'] ?>" data-type="kpi"
															<?php if ($kpi['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit<?= $kpi['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del<?= $kpi['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
												<td class="text-secondary-d3 text-95">
													<a class="btn btn-primary text-600 btn-xs mr-1" href="#modal-add-detail<?= $kpi['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-list-ul"></i>
														รายละเอียด

													</a>
													<?php if (!empty($kpi['code_symbol'])) { ?>
														<i class="fas fa-check-double text-success"></i>
													<?php } else { ?>
														<i class="fas fa-times text-danger"></i>
													<?php } ?>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del<?= $kpi['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
												<div class="modal-dialog " role="document">
													<div class="modal-content bgc-transparent brc-danger-m2 shadow">
														<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
															<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="dangerModalLabel">
																WARNING!
															</h5>

															<button type="button" class="position-tr btn btn-xs btn-outline-white btn-h-yellow btn-a-yellow mt-1px mr-1px btn-brc-tp" data-dismiss="modal" aria-label="Close">
																<span aria-hidden="true" class="text-150">&times;</span>
															</button>
														</div>


														<div class="modal-body bgc-white-tp2 p-md-4 pl-md-5">
															<div class="d-flex align-items-top mr-2 mr-md-5">
																<i class="fas fa-exclamation-triangle fa-2x text-orange-d2 float-rigt mr-4 mt-1"></i>
																<div class="text-secondary-d2 text-105">
																	<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบ!</u></i></b> ข้อมูลนี้</h5>

																</div>
															</div>
														</div>

														<div class="modal-footer bgc-white-tp2 border-0">
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $kpi['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">

																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit<?= $kpi['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-pen text-secondary"></i> แก้ไข จัดการตัวชี้วัด
															</h5>

															<button type="button" class="close" data-dismiss="modal" aria-label="Close">
																<span aria-hidden="true">&times;</span>
															</button>

														</div>

														<div class="modal-body bg-white">
															<div class="bgc-white radius-1">
																<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=update_data" method="POST">

																	<div class="row col-12 mt-2 mt-md-4">
																		<!-- the left side profile picture and other info -->

																		<div class="row col-md-12">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				ข้อมูลตัวชี้วัด
																			</div>
																			<div class=col-md-8>
																				<textarea class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required /><?= $kpi['name']; ?></textarea>

																			</div>
																		</div>


																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $kpi['id']; ?>">

																	<input type="hidden" name="type" value="edit_kpi">
																	<div class="offset-md-4 col-md-8 text-nowrap mt-3">
																		<button class="btn btn-warning btn-bold px-4" type="submit">
																			<i class="fa fa-check mr-1"></i>
																			บันทึกการแก้ไขข้อมูล
																		</button>

																		<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
																			<i class="fa fa-undo mr-1"></i>
																			ค่าเริ่มต้น
																		</button>
																	</div>
																</form>
															</div>
														</div>



														<div class="modal-footer mt-3">
															<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
																<i class="ace-icon fa fa-times"></i>
																Close
															</button>


														</div>

													</div><!-- /.modal-content -->
												</div><!-- /.modal-dialog -->
											</div>
											<!-- จบ แก้ไข -->
											<!-- เพิ่มรายละเอียด -->
											<div class="modal fade modal-lg" id="modal-add-detail<?= $kpi['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-plus text-secondary"></i> กำหนด รายละเอียดข้อมูลตัวชี้วัด
															</h5>

															<button type="button" class="close" data-dismiss="modal" aria-label="Close">
																<span aria-hidden="true">&times;</span>
															</button>

														</div>

														<div class="modal-body bg-white">
															<div class="bgc-white radius-1">
																<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data" method="POST">
																	<label class="text-pink"> ตัวชี้วัด : <?php echo htmlspecialchars($kpi['name']); ?></label>
																	<div class="row col-12 mt-2 mt-md-4">
																		<!-- the left side profile picture and other info -->
																		<div class="row col-md-12">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				* เครื่องหมาย
																			</div>
																			<div class=col-md-8>

																				<?php
																				$symbol_list = getSymbolSelect($db_KPI);

																				$id_selected = $kpi['code_symbol'] ?? null; // รับค่า id ที่ส่งเข้ามา

																				// สร้าง select
																				echo '<select name="code_symbol" id="code_symbol" class="form-control">';

																				foreach ($symbol_list as $symbol) {
																					$selected = ($id_selected == $symbol['id']) ? 'selected' : '';
																					echo '<option value="' . htmlspecialchars($symbol['id']) . '" ' . $selected . '>'
																						. htmlspecialchars($symbol['name'])
																						. '</option>';
																				}

																				echo '</select>';
																				?>

																			</div>
																		</div>
																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				* หน่วยนับ
																			</div>
																			<div class=col-md-8>

																				<?php
																				$unit_list = getAddUnitSelect($db_KPI);

																				$id_selected = $kpi['code_unit']  ?? null; // รับค่า id ที่ส่งเข้ามา

																				// สร้าง select
																				echo '<select name="code_unit" id="code_unit" class="form-control">';

																				foreach ($unit_list as $unit) {
																					$selected = ($id_selected == $unit['id']) ? 'selected' : '';
																					echo '<option value="' . htmlspecialchars($unit['id']) . '" ' . $selected . '>'
																						. htmlspecialchars($unit['name'])
																						. '</option>';
																				}

																				echo '</select>';
																				?>

																			</div>
																		</div>
																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				* การนับ
																			</div>
																			<div class=col-md-8>

																				<?php
																				$count_list = getCountSelect($db_KPI);

																				$id_selected = $kpi['code_count']  ?? null; // รับค่า id ที่ส่งเข้ามา

																				// สร้าง select
																				echo '<select name="code_count" id="code_count" class="form-control">';

																				foreach ($count_list as $count) {
																					$selected = ($id_selected == $count['id']) ? 'selected' : '';
																					echo '<option value="' . htmlspecialchars($count['id']) . '" ' . $selected . '>'
																						. htmlspecialchars($count['name'])
																						. '</option>';
																				}

																				echo '</select>';
																				?>

																			</div>
																		</div>
																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				* คำนิยาม/เงื่อนไขการนับ
																			</div>
																			<div class=col-md-8>

																				<textarea class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="conditions" required /><?= $kpi['conditions']; ?></textarea>


																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $kpi['id']; ?>">

																	<input type="hidden" name="type" value="detail_kpi">

																	<div class="offset-md-4 col-md-8 text-nowrap mt-3">
																		<button class="btn btn-info btn-bold px-4" type="submit">
																			<i class="fa fa-check mr-1"></i>
																			บันทึกข้อมูล
																		</button>

																		<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
																			<i class="fa fa-undo mr-1"></i>
																			ค่าเริ่มต้น
																		</button>
																	</div>
																</form>
															</div>
														</div>



														<div class="modal-footer mt-3">
															<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
																<i class="ace-icon fa fa-times"></i>
																Close
															</button>


														</div>

													</div><!-- /.modal-content -->
												</div><!-- /.modal-dialog -->
											</div>
											<!-- จบ เพิ่ม -->

										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
										</tr>
									<?php endif; ?>

								</tbody>
							</table>
							<!-- เพิ่ม -->
							<div class="modal fade modal-lg" id="modal-add-set-mark" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-plus text-secondary"></i> เพิ่ม ข้อมูลตัวชี้วัด
											</h5>

											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>

										</div>

										<div class="modal-body bg-white">
											<div class="bgc-white radius-1">
												<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data" method="POST">

													<div class="row col-12 mt-2 mt-md-4">
														<!-- the left side profile picture and other info -->

														<div class="col-md-12">
															<table class="table table table-striped-default table-borderless">
																<tr>
																	<td>

																		<i class="fas fa-layer-group text-blue"></i>
																	</td>
																	<td class="text-95 text-600 text-secondary-d2">
																		ข้อมูลตัวชี้วัด
																	</td>
																	<td class="text-dark-m3">
																		<textarea class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required /></textarea>
																	</td>
																</tr>


															</table>
														</div>
													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">

													<input type="hidden" name="type" value="add_kpi">
													<div class="offset-md-4 col-md-8 text-nowrap mt-3">
														<button class="btn btn-info btn-bold px-4" type="submit">
															<i class="fa fa-check mr-1"></i>
															บันทึกข้อมูล
														</button>

														<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
															<i class="fa fa-undo mr-1"></i>
															ค่าเริ่มต้น
														</button>
													</div>
												</form>
											</div>
										</div>



										<div class="modal-footer mt-3">
											<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
												<i class="ace-icon fa fa-times"></i>
												Close
											</button>


										</div>

									</div><!-- /.modal-content -->
								</div><!-- /.modal-dialog -->
							</div>
							<!-- จบ เพิ่ม -->
							<!-- Import Excel -->
							<div class="modal fade modal-lg" id="modal-import-data" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-file-import text-secondary"></i> นำเข้า ข้อมูลตัวชี้วัด Import Excel File
											</h5>

											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>

										</div>

										<div class="modal-body bg-white">
											<div class="bgc-white radius-1">
												<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=upload_data" method="POST">

													<div class="row col-12 mt-2 mt-md-4">
														<!-- the left side profile picture and other info -->

														<div class="col-md-12">
															<table class="table table table-striped-default table-borderless">
																<tr>
																	<td>

																		<i class="fas fa-layer-group text-blue"></i>
																	</td>
																	<td class="text-95 text-600 text-secondary-d2">
																		ข้อมูลตัวชี้วัด excel File (.xlsx) <br>(Column = ชื่อตัวชี้วัด)
																	</td>
																	<td class="text-dark-m3">
																		<!-- <input type="file" class="ace-file-input" id="ace-file-input1" /> -->

																		<input type='file' class="ace-file-input" id="ace-file-input1" accept=".xlsx" name="excel_file" required />
																		<p class="text-danger text-80">**ไฟล์ต้องเป็นนามสกุล .xlsx เท่านั้น**</p>
																	</td>
																</tr>


															</table>
														</div>
													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">


													<input type="hidden" name="type" value="upload_kpi">

													<div class="offset-md-4 col-md-8 text-nowrap mt-3">
														<button class="btn btn-info btn-bold px-4" type="submit">
															<i class="fa fa-check mr-1"></i>
															บันทึกข้อมูล
														</button>

														<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
															<i class="fa fa-undo mr-1"></i>
															ค่าเริ่มต้น
														</button>
													</div>
												</form>
											</div>
										</div>



										<div class="modal-footer mt-3">
											<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
												<i class="ace-icon fa fa-times"></i>
												Close
											</button>


										</div>

									</div><!-- /.modal-content -->
								</div><!-- /.modal-dialog -->
							</div>
							<!-- จบ Import -->
						</div><!-- /.table-responsive-md -->

					</div><!-- /.card-body -->
				</div>
			</div>
		</div>
	<?php }  //set mark
	?>

	<?php
	switch ($op) {
		case "kpi":
			kpi();
			break;

		case "save_data":

			include("../../../config/connect_db.php");
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];

			$type = $_POST['type'];
			if ($type == "add_kpi") {

				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "INSERT INTO kpi(name,status)VALUE('$name','$status')");
			} elseif ($type == "edit_kpi") {
				$id = $_POST['id'];
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "UPDATE kpi SET name='$name' WHERE id='$id' ");
			} elseif ($type == "detail_kpi") {
				$id = $_POST['id'];
				$name = $_POST['name'];
				$code_symbol = $_POST['code_symbol'];
				$code_unit = $_POST['code_unit'];
				$code_count = $_POST['code_count'];
				$conditions = $_POST['conditions'];
				$sql = mysqli_query($db_KPI, "UPDATE kpi SET code_symbol='$code_symbol',code_unit='$code_unit',code_count='$code_count',conditions='$conditions' WHERE id='$id' ");
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

			include("../../../config/connect_db.php");
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$op_from = $_POST['op_from'];


			if ($op_from == "kpi") {

				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "UPDATE kpi SET name='$name' WHERE id='$_POST[id]' ");
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
			include("../../../config/connect_db.php");
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$op_from = $_POST['op_from'];
			$tb_data = $_POST['op_from'];

			$id = $_GET['id'];
			if ($id != "") {

				$sql = mysqli_query($db_KPI,  "UPDATE $tb_data SET status='99' WHERE  id='$id' ");
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
		case "upload_data":


			include __DIR__ . "/../fn/main_upload.php";

			$s_staff = mysqli_query($db_person, "SELECT * FROM staff WHERE email='{$_SESSION['r_email']}' AND code='{$_SESSION['code']}'");
			$r_staff = mysqli_fetch_array($s_staff);

			$page    = $_GET['page'] ?? '';
			$op_from = $_POST['op_from'] ?? '';
			$id      = $_GET['id'] ?? '';

			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$type = $_POST['type'] ?? '';
				$filePath = $_FILES['excel_file']['tmp_name'] ?? '';

				if (!file_exists($filePath)) {
					$msg = "ไม่พบไฟล์ Excel";
				} else {
					if ($type === 'upload_kpi') {
						$msg = importKPIExcel($db_KPI, $filePath); // คืนข้อความชัดเจน
					} else {
						$msg = "ไม่พบประเภทการอัปโหลด";
					}
				}
				// SUCCESS?
				$success = (strpos($msg, "นำเข้าเรียบร้อย") !== false);

				echo "<script>
						swal({
							title: '" . ($success ? "สำเร็จ" : "เกิดข้อผิดพลาด") . "',
							text: '$msg',
							icon: '" . ($success ? "success" : "error") . "'
						}).then((v)=>{ window.location='?page=$page&op=$op_from'; });
					</script>";
			}

			// if ($sql) {

			// 	$link = "?page=" . $page . '&op=' . $op_from;
			// 	$message = '<script>';
			// 	$message .= 'swal({';
			// 	$message .= 'title: "Upload ข้อมูลเรียบร้อย .",';
			// 	$message .= 'text: " Success",';
			// 	$message .= 'icon: "success",';
			// 	$message .= '}).then((will) => {
			//                         window.location.assign("';
			// 	$message .= $link;
			// 	$message .= '");});';
			// 	$message .= '</script>';
			// 	echo $message;
			// } else {
			// 	$link = "?page=" . $page . '&op=' . $op_from;
			// 	$message = '<script>';
			// 	$message .= 'swal({';
			// 	$message .= 'title: "Upload ข้อมูลไม่สำเร็จ.",';
			// 	$message .= 'text: " กรุณาลองใหม่อีกครั้งค่ะ.",';
			// 	$message .= 'icon: "error",';
			// 	$message .= '}).then((will) => {
			//                         window.location.assign("';
			// 	$message .= $link;
			// 	$message .= '");});';
			// 	$message .= '</script>';
			// 	echo $message;
			// }



			break;
	}
	?>



</body>
<script>
	$(function() {
		// ✅ ป้องกันการ bind ซ้ำ ถ้ามีการ reload content (เช่น DataTables / Ajax reload)
		$(document).off('change', '.ace-switch');

		// ✅ ผูก event แค่ครั้งเดียว
		$(document).on('change', '.ace-switch', function(e) {
			e.stopImmediatePropagation(); // ❗หยุด event ซ้ำจาก Ace Template
			e.preventDefault();

			const $this = $(this);
			const id = $this.data('id');
			const type = $this.data('type');
			const status = $this.is(':checked') ? 1 : 0;

			// ✅ ปิดการใช้งาน checkbox ชั่วคราว ป้องกัน click ซ้ำ
			$this.prop('disabled', true);

			$.ajax({
				url: "update_status.php",
				type: "POST",
				data: {
					id: id,
					status: status,
					type: type
				},
				success: function(response) {
					const trimmed = response.trim();
					console.log("Response:", trimmed);

					if (trimmed === "success") {
						// ใช้ toast / SweetAlert แทน alert
						Swal.fire({
							icon: "success",
							title: "บันทึกเรียบร้อยแล้ว",
							timer: 1200,
							showConfirmButton: false
						});
					} else if (trimmed === "no_change") {
						console.log("ไม่มีการเปลี่ยนแปลง");
					} else {
						Swal.fire({
							icon: "error",
							title: "เกิดข้อผิดพลาด",
							text: trimmed
						});
					}
				},
				error: function(xhr, status, error) {
					Swal.fire({
						icon: "error",
						title: "AJAX Error",
						text: error
					});
				},
				complete: function() {
					// ✅ เปิดการใช้งาน checkbox กลับหลังบันทึกเสร็จ
					$this.prop('disabled', false);
				}
			});
		});
	});
</script>
<script>
	$(document).ready(function() {
		$('#table_indicator').DataTable({
			language: {
				search: "🔍 ค้นหา:",
				lengthMenu: "แสดง _MENU_ แถวต่อหน้า",
				info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
				infoEmpty: "ไม่มีข้อมูล",
				zeroRecords: "ไม่พบข้อมูลที่ค้นหา",
				paginate: {
					first: "หน้าแรก",
					last: "หน้าสุดท้าย",
					next: "ถัดไป",
					previous: "ก่อนหน้า"
				},
			},
			pageLength: 10, // ✅ จำนวนแถวต่อหน้า
			order: [
				[0, 'asc']
			], // ✅ เรียงตามคอลัมน์แรก
			columnDefs: [{
					orderable: false,
					targets: [2, 3, 4]
				} // ✅ ปิด sort สำหรับคอลัมน์ที่มีปุ่ม
			]
		});
	});
</script>
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
		$years_select = $_GET['years_select'] ?? $_POST['years_select'] ?? (date("Y") + 543);
		$keywords = $_POST['keywords'] ?? '';
		$years_select = $years_select;
		$kpi_active = getKPIYear($db_KPI, $years_select);
		// $kpi_list_keyword = getKPIByYearAndKeyword($db_connection, $years_select, $keywords);

		if ($kpi_active['actived'] == '1') {
			$kpi_active_status = 'กำลังใช้งาน';
			$kpi_active_status_color = 'success';
			$kpi_active_status_icon = 'check';
			$kpi_active_status_modal_text = 'ปิดใช้งาน';
			$kpi_active_status_modal_color = 'danger';
			$kpi_active = '0';
		} else {
			$kpi_active_status = 'ยังไม่ได้เปิดใช้งาน';
			$kpi_active_status_color = 'warning';
			$kpi_active_status_icon = 'times';
			$kpi_active_status_modal_text = 'เปิดใช้งาน';
			$kpi_active_status_modal_color = 'green';
			$kpi_active = '1';
		}
	?>
		<!-- <form class="mt-lg-3" autocomplete="off" action="?page=<?= $page; ?>&op=kpi&years_select=<?= $years_select; ?>" method="POST">
			<div class="form-group row">
				<div class="col-sm-3 col-form-label text-sm-right ">
					<label for="id-form-field-1" class="mb-0">
						ค้นหาข้อมูล
					</label>
				</div>

				<div class="col-sm-6">

					<input type="text" name="keywords" placeholder="<?= $keywords; ?>" id="id-form-field-1" class="form-control col-sm-12 col-md-12 bgc-yellow-l1 brc-dark no-border">



				</div>
				<div class="col-sm-3">
					<button class="btn btn-info btn-bold px-4" type="submit">
						<i class="fa fa-search mr-1"></i>
						ค้นหา
					</button>
				</div>
			</div>
		</form> -->


		<div class="row mt-4">
			<!-- orders table -->
			<div class="col-12 col-lg-12">
				<div class="bcard card h-100 pt-1 pt-lg-2">

					<div class="card-header border-0">
						<h5 class="card-title text-dark-m2 pl-1 text-110">
							<i class="fas fa-list-ul"></i> ฐานข้อมูลตัวชี้วัด ประจำปี <span class="m-1 badge bgc-green-l2 radius-round text-success-d3 px-3 text-110">
								<?= $years_select; ?>
							</span>

							<a class="btn btn-<?= $kpi_active_status_color; ?> text-600 btn-xs mr-1" href="#modal-actived<?= $years_select; ?>" role="button" data-toggle="modal">
								<i class="fas fa-<?= $kpi_active_status_icon; ?>"></i> <?= $kpi_active_status; ?>
							</a>

						</h5>
						<div class="card-toolbar align-self-center no-border">
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-mark<?= $years_select; ?>" role="button" data-toggle="modal">
									<i class="fas fa-plus"></i> สร้างข้อมูลตัวชี้วัด ประจำปีงบประมาณ
								</a>
							</div>
						</div>


					</div>
					<div class="card-body p-0">
						<div class="table-responsive-md">
							<table id="table_indicator_KPI" class="table table-border-x brc-secondary-l3 mb-0">
								<thead>
									<tr class="text-80 text-default-d3 bgc-default-l4">
										<th class="pl-2 pl-lg-4">ลำดับที่</th>
										<th>ข้อมูลตัวชี้วัด</th>
										<th>จัดการข้อมูล</th>
									</tr>
								</thead>

								<tbody>
									<?php
									if (!empty($keywords)) {
										$kpi_year_data = getKPIByYearAndKeyword($db_KPI, $years_select, $keywords);
									} else {
										$kpi_year_data = getKPIYearList($db_KPI, $years_select);
									}

									if (!empty($kpi_year_data)) :
										foreach ($kpi_year_data as $kpi_year) :
											$kpi = getKPI($db_KPI, $kpi_year['code_kpi']);
											$kpi_year_id = $kpi_year['code_kpi'];
									?>
											<tr class="bgc-h-secondary-l4">
												<td></td> <!-- ✅ DataTables จะใส่ลำดับอัตโนมัติ -->
												<td class="text-dark-m2 text-85"><?= htmlspecialchars($kpi['name']); ?></td>
												<td class="text-secondary-d3 text-95">
													<a class="btn btn-secondary text-600 btn-xs mr-1"
														href="?op=set_kpi&page=<?= $page; ?>&years_select=<?= $years_select; ?>&kpi_year_id=<?= $kpi_year_id; ?>">
														<i class="fas fa-cog"></i> ตั้งค่า
													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1"
														href="#modal-del<?= $kpi_year['id']; ?>"
														data-toggle="modal">
														<i class="fas fa-trash"></i> ลบ
													</a>
												</td>
											</tr>


											<!-- ลบ -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del<?= $kpi_year['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $kpi_year['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<input type="hidden" name="tb_data" value="kpi_year">
																<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- จบลบ -->
											<!-- 	เปิดใช้งาน -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-actived<?= $years_select; ?>" tabindex="-1" role="dialog" aria-labelledby="greenModalLabel" aria-hidden="true">
												<div class="modal-dialog " role="document">
													<div class="modal-content bgc-transparent brc-<?= $kpi_active_status_modal_color; ?>-m2 shadow">
														<div class="modal-header py-2 bgc-<?= $kpi_active_status_modal_color; ?>-tp1 border-0  radius-t-1">
															<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="greenModalLabel">
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
																	<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u><?= $kpi_active_status_modal_text; ?>!</u></i></b> ข้อมูลตัวชี้วัด ปีงบประมาณ <b><i><u><?= $years_select; ?></u></i></b></h5>

																</div>
															</div>
														</div>

														<div class="modal-footer bgc-white-tp2 border-0">
															<form action="?page=<?= $page; ?>&op=actived" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																<input type="hidden" name="actived" value="<?= $kpi_active; ?>">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- เปิดใช้งาน -->
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-setting<?= $kpi_year['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> ตั้งค่า ตัวชี้วัด
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
																	<input type="hidden" name="years_select" value="<?= $years_select; ?>">
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

																				$id_selected = $kpi['code_unit'] ?? null; // รับค่า id ที่ส่งเข้ามา

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

																				$id_selected = $kpi['code_count'] ?? null; // รับค่า id ที่ส่งเข้ามา

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
																	<input type="hidden" name="years_select" value="<?= $years_select; ?>">
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
							<div class="modal fade modal-xl" id="modal-add-set-mark<?= $years_select; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-plus text-secondary"></i> สร้าง ข้อมูลตัวชี้วัด ประจำปีงบประมาณ <?= $years_select; ?>
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
															<label for="code_kpi" class="mb-0">
																* รายการตัวชี้วัด (สามารถเลือกได้มากกว่า 1 ตัวชี้วัด)
															</label>
															<?php

															$kpi_list = getKPIList($db_KPI);

															?>

															<?php $selected = $_POST['code_kpi'] ?? []; ?>

															<select name="code_kpi[]" id="code_kpi" class="form-control" multiple>
																<?php foreach ($kpi_list as $kpi): ?>
																	<option value="<?= htmlspecialchars($kpi['id']) ?>"
																		<?= in_array($kpi['id'], $selected) ? 'selected' : '' ?>>
																		+ <?= htmlspecialchars($kpi['name']) ?>
																	</option>
																<?php endforeach; ?>
															</select>

														</div>
													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">
													<input type="hidden" name="years_select" value="<?= $years_select; ?>">
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
										<script>
											$(document).ready(function() {
												// ใช้ event ตอน modal เปิด
												$('#modal-add-set-mark<?= $years_select; ?>').on('shown.bs.modal', function() {
													$('#code_kpi').select2({
														placeholder: 'ค้นหาตัวชี้วัด...',
														width: '100%',
														dropdownParent: $("#modal-add-set-mark<?= $years_select; ?>")
													});
												});
											});
										</script>


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

						</div><!-- /.table-responsive-md -->

					</div><!-- /.card-body -->
				</div>
			</div>
		</div>
	<?php }  //set mark
	?>
	<?php function set_kpi()
	{
		include("../../../config/connect_db.php");
		$page = $_GET['page'];
		$op_from = $_GET['op'];
		$years_select = $_GET['years_select'] ?? $_POST['years_select'] ?? (date("Y") + 543);
		$kpi_year_id = $_GET['kpi_year_id'] ?? '';
		$years_select = $years_select;
		$kpi = getKPI($db_KPI, $kpi_year_id);
		$kpi_year = getKPIYearID($db_KPI, $years_select, $kpi_year_id);
		$kpi_year_indicator = getKPIYearIndicator($db_KPI, $kpi_year['id']);
		$symbol = getSetSymbol($db_KPI, $kpi['code_symbol']);
		$unit = getSetUnit($db_KPI, $kpi['code_unit']);
		$count = getSetCount($db_KPI, $kpi['code_count']);
		// echo  $kpi['code_unit'];


	?>
		<h5 class="modal-title text-blue" id="exampleModalLabel2">
			<i class="fas fa-cog text-secondary"></i> ตั้งค่า ข้อมูลตัวชี้วัด ประจำปีงบประมาณ <?= $years_select; ?>
		</h5>
		<div class="page-content container container-plus">
			<div class="card acard mt-2 mt-lg-3">
				<div class="card-header">
					<h3 class="card-title text-125 text-pink-d2">
						<i class="far fa-edit text-dark-l3 mr-1"></i>
						ตัวชี้วัด : <?php echo htmlspecialchars($kpi['name']); ?>

					</h3>
				</div>
				<div class="card-body px-3 pb-1">
					<div class="card shadow-sm mb-3">

						<div class="card-body">
							<div class="row mb-2">
								<div class="col-md-2"><strong>ตัววัดความสำเร็จ :</strong></div>
								<div class="col-md-10  text-info"><?php echo htmlspecialchars($kpi['name']); ?></div>
							</div>
							<div class="row mb-2">
								<div class="col-md-2"><strong>เครื่องหมาย :</strong></div>
								<div class="col-md-10 text-info"><?php echo htmlspecialchars($symbol['name']); ?></div>
							</div>
							<div class="row mb-2">
								<div class="col-md-2"><strong>หน่วยนับ :</strong></div>
								<div class="col-md-10 text-info"><?php echo htmlspecialchars($unit['name']); ?></div>
							</div>
							<div class="row mb-2">
								<div class="col-md-2"><strong>การนับ :</strong></div>
								<div class="col-md-10 text-info"><?php echo htmlspecialchars($count['name']); ?></div>
							</div>
							<div class="row">
								<div class="col-md-2"><strong>คำนิยาม/เงื่อนไขการนับ :</strong></div>
								<div class="col-md-10 text-info">
									<?php echo htmlspecialchars($kpi['conditions']); ?>
								</div>
							</div>
						</div>
					</div>
					<form class="mt-lg-3" autocomplete="off" action="?page=<?= $page; ?>&op=save_indicator&id=<?= $kpi_year['id']; ?>" method="POST">


						<div class="form-group row" id="type_group">
							<div class="col-sm-3 col-form-label text-sm-right pr-0">
								<label for="id-form-field-1" class="mb-0">
									ประเภทตัวชี้วัด

								</label>
							</div>

							<div class="col-sm-9">

								<?php
								$type_list = getTypeSelect($db_KPI);

								$code_type_selected = $kpi_year_indicator['code_type']; // รับค่า id ที่ส่งเข้ามา

								// สร้าง select
								echo '<select name="code_type" id="code_type" class="form-control text-primary">';

								foreach ($type_list as $type1) {
									$selected = ($code_type_selected == $type1['id']) ? 'selected' : '';
									echo '<option value="' . htmlspecialchars($type1['id']) . '" ' . $selected . '>'
										. htmlspecialchars($type1['name'])
										. '</option>';
								}

								echo '</select>';
								?>
							</div>
						</div>
						<div class="form-group row" id="so_group" style="display: none;">
							<div class="col-sm-3 col-form-label text-sm-right pr-0">
								<label for="id-form-field-1" class="mb-0">
									SO
								</label>
							</div>

							<div class="col-sm-9">
								<?php
								$so_list = getSOSelect($db_KPI);

								$code_so_selected = $kpi_year_indicator['code_so']; // รับค่า id ที่ส่งเข้ามา

								// สร้าง select
								echo '<select name="code_so" id="code_so" class="form-control text-primary">';
								echo '<option value="">เลือก</option>';
								foreach ($so_list as $so) {
									$selected = ($code_so_selected == $so['id']) ? 'selected' : '';
									echo '<option value="' . htmlspecialchars($so['id']) . '" ' . $selected . '>'
										. htmlspecialchars($so['name'])
										. '</option>';
								}

								echo '</select>';
								?>
							</div>
						</div>
						<div class="form-group row" id="side_group">
							<div class="col-sm-3 col-form-label text-sm-right pr-0">
								<label for="id-form-field-1" class="mb-0">
									ด้าน
								</label>
							</div>

							<div class="col-sm-9">
								<?php
								$side_list = getSideSelect($db_KPI);

								$code_side_selected = $kpi_year_indicator['code_side']; // รับค่า id ที่ส่งเข้ามา

								// สร้าง select
								echo '<select name="code_side" id="code_side" class="form-control text-primary">';
								echo '<option value="">เลือก</option>';
								foreach ($side_list as $side) {
									$selected = ($code_side_selected == $side['id']) ? 'selected' : '';

									echo '<option value="' . htmlspecialchars($side['id']) . '" ' . $selected . '>'
										. htmlspecialchars($side['name'])
										. '</option>';
								}

								echo '</select>';
								?>
							</div>
						</div>
						<div class="form-group row" id="ap_group" style="display: none;">
							<div class="col-sm-3 col-form-label text-sm-right pr-0">
								<label for="id-form-field-1" class="mb-0">
									แผนปฏิบัติการ (AP)
								</label>
							</div>

							<div class="col-sm-9">
								<?php
								$ap_list = getAPSelect($db_KPI);

								$code_ap_selected = $kpi_year_indicator['code_ap']; // รับค่า id ที่ส่งเข้ามา

								// สร้าง select

								echo '<select name="code_ap" id="code_ap" class="form-control text-primary">';
								echo '<option value="">เลือก</option>';
								foreach ($ap_list as $ap) {
									$selected = ($code_ap_selected == $ap['id']) ? 'selected' : '';
									echo '<option value="' . htmlspecialchars($ap['id']) . '" ' . $selected . '>'
										. htmlspecialchars($ap['name'])
										. '</option>';
								}

								echo '</select>';
								?>

							</div>
						</div>
						<script>
							$(document).ready(function() {
								// อ่านค่าที่ PHP set มา
								let type = $("#code_type").val();

								// แสดง/ซ่อน group ตามค่าที่เลือกมา
								if (type === "2") {
									// ถ้าเลือกแผนกลยุทธ์
									$("#so_group").show();
									$("#ap_group").show();
									$("#side_group").hide();
								} else {
									// ถ้าเลือกแผนงานประจำ
									$("#so_group").hide();
									$("#ap_group").hide();
									$("#side_group").show();
								}

								// event เวลาเปลี่ยน select
								$("#code_type").on("change", function() {
									let type = $(this).val();

									if (type === "2") {
										$("#so_group").show();
										$("#ap_group").show();
										$("#side_group").hide();
									} else {
										$("#so_group").hide();
										$("#ap_group").hide();
										$("#side_group").show();
									}
								});
							});
						</script>
						<span class="badge bgc-default pos-rel text-white radius-4 px-3 text-110">
							<span class="bgc-default opacity-5 position-tl h-100 w-100 radius-4"></span>
							<span class="pos-rel"><i class="far fa-flag"></i>
								เป้าหมายในแต่ละปี
							</span>

						</span>
						<a class="btn btn-primary text-600 btn-xs mr-1" href="#modal-add-target<?= $kpi_year['id']; ?>" role="button" data-toggle="modal">
							<i class="fas fa-plus"></i>
							เพิ่มเป้าหมาย

						</a>
						<?php

						$kpi_year_target = getKPIYearTargetList($db_KPI, $kpi_year['id']);
						?>

						<?php if (!empty($kpi_year_target)): ?>
							<table class="table table-bordered text-center mt-3">
								<thead class="table-light">
									<tr>
										<th>ปี </th>
										<?php foreach ($kpi_year_target as $target): ?>
											<th> <?= htmlspecialchars($target['target_year']) ?>

											</th>
										<?php endforeach; ?>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>ค่าเป้า</td>
										<?php foreach ($kpi_year_target as $target): ?>
											<td><?= htmlspecialchars($target['target']) ?></td>
										<?php endforeach; ?>
									</tr>
									<tr>
										<td>จัดการ</td>
										<?php foreach ($kpi_year_target as $target): ?>
											<td>

												<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-target<?= $target['id']; ?>" role="button" data-toggle="modal">
													<i class="fa fa-edit"></i>
													แก้ไข

												</a>
												<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del<?= $target['id']; ?>" role="button" data-toggle="modal">
													<i class="fas fa-trash"></i>
													ลบ

												</a>
												<!-- ลบ -->
												<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del<?= $target['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
																<form action="?page=<?= $page; ?>&op=del_target_all&id=<?= $target['id']; ?>" method="POST">
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																	<input type="hidden" name="kpi_year_id" value="<?= $kpi_year_id; ?>">
																	<input type="hidden" name="tb_data" value="kpi_year_target">
																	<button class="btn btn-danger" type="submit">ยืนยัน </button>

																</form>
															</div>
														</div>
													</div>
												</div>
												<!-- จบลบ -->
												<!-- แก้ไข -->
												<div class="modal fade modal-lg" id="modal-edit-target<?= $target['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

													<div class="modal-dialog " role="document">
														<div class="modal-content">

															<div class="modal-header">
																<h5 class="modal-title text-blue" id="exampleModalLabel2">
																	<i class="fas fa-cog text-secondary"></i> แก้ไข เป้าหมาย ตัวชี้วัดประจำปี <?= $years_select; ?>
																</h5>

																<button type="button" class="close" data-dismiss="modal" aria-label="Close">
																	<span aria-hidden="true">&times;</span>
																</button>

															</div>

															<div class="modal-body bg-white">
																<div class="bgc-white radius-1">
																	<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=update_target_data" method="POST">

																		<div class="row col-12 mt-2 mt-md-4">
																			<!-- the left side profile picture and other info -->


																			<div class="row col-md-12 mt-3">
																				<div class="col-md-4 text-right">

																					ปี
																				</div>
																				<div class=col-md-8>
																					<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="target_year" value="<?= $target['target_year']; ?>" maxlength="4" placeholder="2568" required />

																				</div>
																			</div>
																			<div class="row col-md-12 mt-3">
																				<div class="col-md-4 text-right">

																					ค่าเป้าหมาย
																				</div>
																				<div class=col-md-8>
																					<input type="number" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="target" value="<?= $target['target']; ?>" required />


																				</div>
																			</div>

																		</div>
																		<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																		<input type="hidden" name="id" value="<?= $target['id']; ?>">
																		<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																		<input type="hidden" name="kpi_year_id" value="<?= $kpi_year_id; ?>">
																		<input type="hidden" name="type" value="target">
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

											</td>
										<?php endforeach; ?>
									</tr>
								</tbody>
							</table>


						<?php else: ?>
							<p class="text-danger">ไม่พบข้อมูลเป้าหมาย</p>
						<?php endif; ?>


						<div class="form-group row">
							<div class="col-sm-3 col-form-label text-sm-right pr-0">
								<label for="id-form-field-1" class="mb-0">
									ค่าน้ำหนักของตัวชี้วัด
								</label>
							</div>

							<div class="col-sm-9">
								<input type="number" class="form-control text-primary col-sm-12 col-md-12" id="id-form-field-1" name="weight_target" value="<?= $kpi_year_indicator['weight_target']; ?>" required />

							</div>
						</div>
						<span class="badge bgc-default pos-rel text-white radius-4 px-3 text-110">
							<span class="bgc-default opacity-5 position-tl h-100 w-100 radius-4"></span>
							<span class="pos-rel"><i class="far fa-user"></i>
								ผู้รับผิดชอบข้อมูล
							</span>

						</span>



						<table class="table table-bordered text-center mt-3">
							<thead class="table-light">
								<tr>
									<th>ผู้รายงาน

										<a class="btn btn-primary text-600 btn-xs mr-1" href="#modal-add-user<?= $kpi_year['id']; ?>" role="button" data-toggle="modal">
											<i class="fas fa-plus"></i>
											เพิ่มผู้รายงาน

										</a>
									</th>
									<th>ผู้รับผิดชอบ

										<a class="btn btn-primary text-600 btn-xs mr-1" href="#modal-add-user-kpi<?= $kpi_year['id']; ?>" role="button" data-toggle="modal">
											<i class="fas fa-plus"></i>
											เพิ่มผู้รับผิดชอบ

										</a>
									</th>
								</tr>
							</thead>
							<tbody>

								<tr>
									<td>
										<?php

										$kpi_year_data_owner = getKPIYearDataOwnerList($db_KPI, $kpi_year['id']);
										?>

										<?php if (!empty($kpi_year_data_owner)): ?>
											<?php foreach ($kpi_year_data_owner as $data_owner):
												$owner = getSetOwner($db_KPI, $data_owner['code_unit']);
											?>
												<span class="m-1 badge bgc-secondary-l3 radius-2 text-secondary-d3 text-80 px-3">
													<?= htmlspecialchars($owner['name']) ?> <a class="btn btn-danger  btn-xs mr-1" href="#modal-del<?= $data_owner['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>


													</a>
												</span>


												<!-- ลบ -->
												<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del<?= $data_owner['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
																<form action="?page=<?= $page; ?>&op=del_target_all&id=<?= $data_owner['id']; ?>" method="POST">
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																	<input type="hidden" name="kpi_year_id" value="<?= $kpi_year_id; ?>">
																	<input type="hidden" name="tb_data" value="kpi_year_data_owner">
																	<button class="btn btn-danger" type="submit">ยืนยัน </button>

																</form>
															</div>
														</div>
													</div>
												</div>
												<!-- จบลบ -->
											<?php endforeach; ?>
										<?php else: ?>
											<p class="text-danger">ไม่พบข้อมูลผู้รายงาน</p>
										<?php endif; ?>

									</td>

									<td>
										<?php

										$kpi_year_kpi_owner = getKPIYearKPIOwnerList($db_KPI, $kpi_year['id']);
										?>

										<?php if (!empty($kpi_year_kpi_owner)): ?>
											<?php foreach ($kpi_year_kpi_owner as $kpi_owner):
												$owner = getSetOwner($db_KPI, $kpi_owner['code_unit']);
											?>
												<span class="m-1 badge bgc-green-l3 radius-2 text-secondary-d3 text-80 px-3">
													<?= htmlspecialchars($owner['name']) ?> <a class="btn btn-danger  btn-xs mr-1" href="#modal-del<?= $kpi_owner['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>


													</a>
												</span>


												<!-- ลบ -->
												<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del<?= $kpi_owner['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
																<form action="?page=<?= $page; ?>&op=del_target_all&id=<?= $kpi_owner['id']; ?>" method="POST">
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="years_select" value="<?= $years_select; ?>">
																	<input type="hidden" name="kpi_year_id" value="<?= $kpi_year_id; ?>">
																	<input type="hidden" name="tb_data" value="kpi_year_kpi_owner">
																	<button class="btn btn-danger" type="submit">ยืนยัน </button>

																</form>
															</div>
														</div>
													</div>
												</div>
												<!-- จบลบ -->
											<?php endforeach; ?>
										<?php else: ?>
											<p class="text-danger">ไม่พบข้อมูลผู้รับผิดชอบ</p>
										<?php endif; ?>
									</td>

								</tr>

							</tbody>
						</table>


						<div class="form-group row">
							<div class="col-sm-3 col-form-label text-sm-right pr-0">
								<label for="id-form-field-1" class="mb-0">
									ค่าน้ำหนักของหน่วยงาน (ผู้รายงาน)
								</label>
							</div>

							<div class="col-sm-9">
								<input type="number" class="form-control text-primary col-sm-12 col-md-12" id="id-form-field-1" name="weight_owner" value="<?= $kpi_year_indicator['weight_owner']; ?>" required />

							</div>
						</div>

						<div class="mt-5 border-t-1 bgc-secondary-l4 brc-secondary-l2 py-35 mx-n25">
							<div class="offset-md-3 col-md-9 text-nowrap">
								<input type="hidden" name="op_from" value="<?= $op_from; ?>">
								<input type="hidden" name="years_select" value="<?= $years_select; ?>">
								<input type="hidden" name="kpi_year_id" value="<?= $kpi_year_id; ?>">
								<input type="hidden" name="code_kpi_year" value="<?= $kpi_year['id']; ?>">
								<input type="hidden" name="type" value="add_indicator">
								<button class="btn btn-info btn-bold px-4" type="submit">
									<i class="fa fa-check mr-1"></i>

									บันทึกข้อมูล
								</button>

								<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
									<i class="fa fa-undo mr-1"></i>
									ค่าเริื่มต้น
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- เพิ่ม Target-->
		<div class="modal fade modal-xl" id="modal-add-target<?= $kpi_year['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

			<div class="modal-dialog " role="document">
				<div class="modal-content">

					<div class="modal-header">
						<h5 class="modal-title text-blue" id="exampleModalLabel2">
							<i class="fas fa-plus text-secondary"></i> เพิ่มเป้าหมาย ตัวชี้วัดประจำปี <?= $years_select; ?>
						</h5>

						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>

					</div>

					<div class="modal-body bg-white">
						<div class="bgc-white radius-1">
							<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_target_data" method="POST">

								<div class="row col-12 mt-2 mt-md-4">
									<!-- the left side profile picture and other info -->

									<div class="row col-md-12 mt-3">
										<div class="col-md-4 text-right">

											ปี
										</div>
										<div class=col-md-8>
											<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="target_year" maxlength="4" placeholder="2568" required />

										</div>
									</div>
									<div class="row col-md-12 mt-3">
										<div class="col-md-4 text-right">

											ค่าเป้าหมาย
										</div>
										<div class=col-md-8>
											<input type="number" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="target" required />


										</div>
									</div>


								</div>
								<input type="hidden" name="op_from" value="<?= $op_from; ?>">
								<input type="hidden" name="years_select" value="<?= $years_select; ?>">
								<input type="hidden" name="kpi_year_id" value="<?= $kpi_year_id; ?>">
								<input type="hidden" name="code_kpi_year" value="<?= $kpi_year['id']; ?>">

								<input type="hidden" name="type" value="add_target">
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
		<!-- เพิ่ม Owner-->
		<div class="modal fade modal-xl" id="modal-add-user<?= $kpi_year['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

			<div class="modal-dialog " role="document">
				<div class="modal-content">

					<div class="modal-header">
						<h5 class="modal-title text-blue" id="exampleModalLabel2">
							<i class="fas fa-plus text-secondary"></i> เพิ่ม Data Owner / KPI Owner ประจำปี <?= $years_select; ?>
						</h5>

						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>

					</div>

					<div class="modal-body bg-white">
						<div class="bgc-white radius-1">
							<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_user_owner" method="POST">

								<div class="row col-12 mt-2 mt-md-4">
									<!-- the left side profile picture and other info -->
									<div class="row col-md-12 mt-3">
										<div class="col-md-4 text-right">

											<label for="code_unit" class="mb-0">
												* ผู้รายงานข้อมูลตัวชี้วัด <br>(สามารถเลือกได้มากกว่า 1 หน่วยงาน)
											</label>
										</div>
										<div class=col-md-8>
											<?php

											$data_owner_list = getOwnerList($db_KPI, '1');  // 1=ผู้รายงาน, 2=ผู้รับผิดชอบ

											?>

											<?php $selected = $_POST['code_unit'] ?? []; ?>

											<select name="code_unit[]" id="code_unit" class="form-control" multiple>
												<?php foreach ($data_owner_list as $data_owner): ?>
													<option value="<?= htmlspecialchars($data_owner['id']) ?>"
														<?= in_array($data_owner['id'], $selected) ? 'selected' : '' ?>>
														+ <?= htmlspecialchars($data_owner['name']) ?>
													</option>
												<?php endforeach; ?>
											</select>
										</div>
									</div>

								</div>
								<input type="hidden" name="op_from" value="<?= $op_from; ?>">
								<input type="hidden" name="years_select" value="<?= $years_select; ?>">
								<input type="hidden" name="kpi_year_id" value="<?= $kpi_year_id; ?>">
								<input type="hidden" name="code_kpi_year" value="<?= $kpi_year['id']; ?>">
								<input type="hidden" name="type" value="add_data_owner">
								<div class="offset-md-4 col-md-8 text-nowrap mt-3">
									<button class="btn btn-info btn-bold px-4" type="submit">
										<i class="fa fa-check mr-1"></i>
										บันทึกข้อมูล<?= $kpi_year['id']; ?>
									</button>

									<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
										<i class="fa fa-undo mr-1"></i>
										ค่าเริ่มต้น
									</button>
								</div>
							</form>
						</div>
					</div>
					<script>
						$(document).ready(function() {
							// ใช้ event ตอน modal เปิด
							$('#modal-add-user<?= $kpi_year['id']; ?>').on('shown.bs.modal', function() {
								$('#code_unit').select2({
									placeholder: 'ค้นหาผู้รายงานข้อมูล...',
									width: '100%',
									dropdownParent: $("#modal-add-user<?= $kpi_year['id']; ?>")
								});
							});
						});
					</script>



					<div class="modal-footer mt-3">
						<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
							<i class="ace-icon fa fa-times"></i>
							Close
						</button>


					</div>

				</div><!-- /.modal-content -->
			</div><!-- /.modal-dialog -->
		</div>
		<!-- จบ เพิ่ม ผู้รายงาน -->
		<!-- เพิ่ม KPI Owner-->
		<div class="modal fade modal-xl" id="modal-add-user-kpi<?= $kpi_year['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

			<div class="modal-dialog " role="document">
				<div class="modal-content">

					<div class="modal-header">
						<h5 class="modal-title text-blue" id="exampleModalLabel2">
							<i class="fas fa-plus text-secondary"></i> เพิ่ม KPI Owner ประจำปี <?= $years_select; ?>
						</h5>

						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>

					</div>

					<div class="modal-body bg-white">
						<div class="bgc-white radius-1">
							<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data_kpi_owner" method="POST">

								<div class="row col-12 mt-2 mt-md-4">
									<!-- the left side profile picture and other info -->
									<div class="row col-md-12 mt-3">
										<div class="col-md-4 text-right">

											<label for="code_unit_owner" class="mb-0">
												* ผู้รับผิดชอบข้อมูลตัวชี้วัด <br>(สามารถเลือกได้มากกว่า 1 คน)
											</label>
										</div>
										<div class=col-md-8>
											<?php

											$kpi_owner_list = getOwnerList($db_KPI, '2');  // 1=ผู้รายงาน, 2=ผู้รับผิดชอบ

											?>

											<?php $selected = $_POST['code_unit_owner'] ?? []; ?>

											<select name="code_unit_owner[]" id="code_unit_owner" class="form-control" multiple>
												<?php foreach ($kpi_owner_list as $kpi_owner): ?>
													<option value="<?= htmlspecialchars($kpi_owner['id']) ?>"
														<?= in_array($kpi_owner['id'], $selected) ? 'selected' : '' ?>>
														+ <?= htmlspecialchars($kpi_owner['name']) ?>
													</option>
												<?php endforeach; ?>
											</select>
										</div>
									</div>

								</div>
								<input type="hidden" name="op_from" value="<?= $op_from; ?>">
								<input type="hidden" name="years_select" value="<?= $years_select; ?>">
								<input type="hidden" name="kpi_year_id" value="<?= $kpi_year_id; ?>">
								<input type="hidden" name="code_kpi_year" value="<?= $kpi_year['id']; ?>">
								<input type="hidden" name="type" value="add_kpi_owner">
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
					<script>
						$(document).ready(function() {
							// ใช้ event ตอน modal เปิด
							$('#modal-add-user-kpi<?= $kpi_year['id']; ?>').on('shown.bs.modal', function() {
								$('#code_unit_owner').select2({
									placeholder: 'ค้นหาผู้รับผิดชอบข้อมูล...',
									width: '100%',
									dropdownParent: $("#modal-add-user-kpi<?= $kpi_year['id']; ?>")
								});
							});
						});
					</script>



					<div class="modal-footer mt-3">
						<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
							<i class="ace-icon fa fa-times"></i>
							Close
						</button>


					</div>

				</div><!-- /.modal-content -->
			</div><!-- /.modal-dialog -->
		</div>
		<!-- จบ เพิ่ม ผู้รับผิดชอบ -->


	<?php }  //
	?>
	<?php
	switch ($op) {
		case "kpi":
		default;
			kpi();
			break;
		case "set_kpi":
			set_kpi();
			break;

		case "save_data":

			include("../../../config/connect_db.php");
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			// $years_select = $_POST['years_select'];
			// $type = $_POST['type'];


			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$years_select = intval($_POST['years_select'] ?? 0);
				$selected_kpis = $_POST['code_kpi'] ?? [];

				if ($years_select > 0 && !empty($selected_kpis)) {

					$success = true;
					$skipped = 0;

					foreach ($selected_kpis as $code_kpi) {
						// ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
						$check = $db_KPI->prepare(
							"SELECT id FROM kpi_year WHERE kpi_year = ? AND code_kpi = ?"
						);
						$check->bind_param("ii", $years_select, $code_kpi);
						$check->execute();
						$check->store_result();

						if ($check->num_rows > 0) {
							// ถ้ามีอยู่แล้ว -> ข้าม
							$skipped++;
							$check->close();
							continue;
						}
						$check->close();

						// ถ้าไม่มี -> insert
						$stmt = $db_KPI->prepare(
							"INSERT INTO kpi_year (kpi_year, code_kpi, status) VALUES (?, ?, 1)"
						);
						$stmt->bind_param("ii", $years_select, $code_kpi);

						if (!$stmt->execute()) {
							$success = false;
						}
						$stmt->close();
					}

					if ($success) {
						$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select;
						echo '<script>
                    swal({
                        title: "บันทึกข้อมูลเรียบร้อย",
                        text: "Success (ข้าม ' . $skipped . ' รายการซ้ำ)",
                        icon: "success",
                    }).then(() => { window.location.assign("' . $link . '"); });
                  </script>';
					} else {
						$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select;
						echo '<script>
                    swal({
                        title: "บันทึกข้อมูลไม่สำเร็จ",
                        text: "กรุณาลองใหม่อีกครั้งค่ะ",
                        icon: "error",
                    }).then(() => { window.location.assign("' . $link . '"); });
                  </script>';
					}
				} else {
					echo "กรุณาเลือกปีงบประมาณและตัวชี้วัด";
				}
			}






			break;
		case "actived":

			include("../../../config/connect_db.php");
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$actived = $_POST['actived'];


			$sql = mysqli_query($db_KPI, "UPDATE kpi_year SET actived='$actived' WHERE kpi_year='$years_select' ");




			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select;
				$message = '<script>';
				$message .= 'swal({';
				$message .= 'title: "จัดการข้อมูลเรียบร้อย .",';
				$message .= 'text: " Success",';
				$message .= 'icon: "success",';
				$message .= '}).then((will) => {
											window.location.assign("';
				$message .= $link;
				$message .= '");});';
				$message .= '</script>';
				echo $message;
			} else {
				$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select;
				$message = '<script>';
				$message .= 'swal({';
				$message .= 'title: "จัดการข้อมูลไม่สำเร็จ.",';
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
			$tb_data = $_POST['tb_data'];
			$years_select = $_POST['years_select'];
			$id = $_GET['id'];
			if ($id != "") {

				$sql = mysqli_query($db_KPI,  "UPDATE $tb_data SET status='99' WHERE  id='$id' ");
			}

			if ($sql) {

				$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select;
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
				$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select;
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
		case "save_target_data":

			include("../../../config/connect_db.php");
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];

			$type = $_POST['type'];
			$code_kpi_year = $_POST['code_kpi_year'];
			$kpi_year_id = $_POST['kpi_year_id'];
			$years_select = $_POST['years_select'];
			if ($type == "add_target") {

				$target_year = $_POST['target_year'];
				$target = $_POST['target'];
				$sql = mysqli_query($db_KPI, "INSERT INTO kpi_year_target(code_kpi_year,target_year,target,status)VALUE('$code_kpi_year','$target_year','$target','$status')");
			}



			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select . '&kpi_year_id=' . $kpi_year_id;

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
				$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select . '&kpi_year_id=' . $kpi_year_id;

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
		case "update_target_data":

			include("../../../config/connect_db.php");
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$op_from = $_POST['op_from'];
			$type = $_POST['type'];
			$kpi_year_id = $_POST['kpi_year_id'];
			$years_select = $_POST['years_select'];
			if ($type == "target") {

				$target_year = $_POST['target_year'];
				$target = $_POST['target'];

				$sql = mysqli_query($db_KPI, "UPDATE kpi_year_target SET target_year='$target_year', target='$target' WHERE id='$_POST[id]' ");
			}



			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select . '&kpi_year_id=' . $kpi_year_id;
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
				$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select . '&kpi_year_id=' . $kpi_year_id;

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
		case "del_target_all":
			include("../../../config/connect_db.php");
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$op_from = $_POST['op_from'];
			$tb_data = $_POST['tb_data'];
			$kpi_year_id = $_POST['kpi_year_id'];
			$years_select = $_POST['years_select'];
			$id = $_GET['id'];
			if ($id != "") {

				$sql = mysqli_query($db_KPI,  "UPDATE $tb_data SET status='99' WHERE  id='$id' ");
			}

			if ($sql) {

				$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select . '&kpi_year_id=' . $kpi_year_id;

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
				$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select . '&kpi_year_id=' . $kpi_year_id;

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
		case "save_data_user_owner":

			include("../../../config/connect_db.php");
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$kpi_year_id = $_POST['kpi_year_id'];


			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$code_kpi_year = $_POST['code_kpi_year'] ?? 0;
				$selected_kpis = $_POST['code_unit'] ?? [];

				if ($code_kpi_year > 0 && !empty($selected_kpis)) {

					$success = true;
					$skipped = 0;

					foreach ($selected_kpis as $code_unit) {
						// ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
						$check = $db_KPI->prepare(
							"SELECT id FROM kpi_year_data_owner WHERE code_kpi_year = ? AND code_unit = ?"
						);
						$check->bind_param("ii", $code_kpi_year, $code_unit);
						$check->execute();
						$check->store_result();

						if ($check->num_rows > 0) {
							// ถ้ามีอยู่แล้ว -> ข้าม
							$skipped++;
							$check->close();
							continue;
						}
						$check->close();

						// ถ้าไม่มี -> insert
						$stmt = $db_KPI->prepare(
							"INSERT INTO kpi_year_data_owner (code_kpi_year, code_unit, status) VALUES (?, ?, 1)"
						);
						$stmt->bind_param("ii", $code_kpi_year, $code_unit);

						if (!$stmt->execute()) {
							$success = false;
						}
						$stmt->close();
					}

					if ($success) {
						$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select . '&kpi_year_id=' . $kpi_year_id;

						echo '<script>
                    swal({
                        title: "บันทึกข้อมูลเรียบร้อย",
                        text: "Success (ข้าม ' . $skipped . ' รายการซ้ำ)",
                        icon: "success",
                    }).then(() => { window.location.assign("' . $link . '"); });
                  </script>';
					} else {
						$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select . '&kpi_year_id=' . $kpi_year_id;

						echo '<script>
                    swal({
                        title: "บันทึกข้อมูลไม่สำเร็จ",
                        text: "กรุณาลองใหม่อีกครั้งค่ะ",
                        icon: "error",
                    }).then(() => { window.location.assign("' . $link . '"); });
                  </script>';
					}
				} else {
					echo "กรุณาเลือกผู้รายงานข้อมูล";
				}
			}
			break;
		case "save_data_kpi_owner":

			include("../../../config/connect_db.php");
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			$years_select = $_POST['years_select'];
			$kpi_year_id = $_POST['kpi_year_id'];


			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$code_kpi_year = $_POST['code_kpi_year'] ?? 0;
				$selected_kpis = $_POST['code_unit_owner'] ?? [];

				if ($code_kpi_year > 0 && !empty($selected_kpis)) {

					$success = true;
					$skipped = 0;

					foreach ($selected_kpis as $code_unit_owner) {
						// ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
						$check = $db_KPI->prepare(
							"SELECT id FROM kpi_year_kpi_owner WHERE code_kpi_year = ? AND code_unit = ?"
						);
						$check->bind_param("ii", $code_kpi_year, $code_unit_owner);
						$check->execute();
						$check->store_result();

						if ($check->num_rows > 0) {
							// ถ้ามีอยู่แล้ว -> ข้าม
							$skipped++;
							$check->close();
							continue;
						}
						$check->close();

						// ถ้าไม่มี -> insert
						$stmt = $db_KPI->prepare(
							"INSERT INTO kpi_year_kpi_owner (code_kpi_year, code_unit, status) VALUES (?, ?, 1)"
						);
						$stmt->bind_param("ii", $code_kpi_year, $code_unit_owner);

						if (!$stmt->execute()) {
							$success = false;
						}
						$stmt->close();
					}

					if ($success) {
						$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select . '&kpi_year_id=' . $kpi_year_id;

						echo '<script>
                    swal({
                        title: "บันทึกข้อมูลเรียบร้อย",
                        text: "Success (ข้าม ' . $skipped . ' รายการซ้ำ)",
                        icon: "success",
                    }).then(() => { window.location.assign("' . $link . '"); });
                  </script>';
					} else {
						$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select . '&kpi_year_id=' . $kpi_year_id;

						echo '<script>
                    swal({
                        title: "บันทึกข้อมูลไม่สำเร็จ",
                        text: "กรุณาลองใหม่อีกครั้งค่ะ",
                        icon: "error",
                    }).then(() => { window.location.assign("' . $link . '"); });
                  </script>';
					}
				} else {
					echo "กรุณาเลือกผู้รับผิดชอบข้อมูล";
				}
			}
			break;
		case "save_indicator":

			include("../../../config/connect_db.php");
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];

			$type = $_POST['type'];
			$code_kpi_year = $_POST['code_kpi_year'];
			$kpi_year_id = $_POST['kpi_year_id'];
			$years_select = $_POST['years_select'];
			$code_type = $_POST['code_type'];
			$code_so = $_POST['code_so'];
			$code_side = $_POST['code_side'];
			$code_ap = $_POST['code_ap'];
			$weight_target = $_POST['weight_target'];
			$weight_owner = $_POST['weight_owner'];
			$s_indicator = mysqli_query($db_KPI, "select * from kpi_year_indicator where code_kpi_year='$code_kpi_year' and status='1'  ");
			$n_indicator = mysqli_num_rows($s_indicator);
			if ($n_indicator == "0") {
				//echo "I";
				$sql = mysqli_query($db_KPI, "INSERT INTO kpi_year_indicator(code_kpi_year,code_type,code_so,code_side,code_ap,weight_target,weight_owner,status)
				VALUE('$code_kpi_year','$code_type','$code_so','$code_side','$code_ap','$weight_target','$weight_owner','$status')");
			} else {
				$s_del = mysqli_query($db_KPI,  "UPDATE kpi_year_indicator SET status='99' WHERE  code_kpi_year='$code_kpi_year' and status='1' ");
				// $sql = mysqli_query($db_KPI, "UPDATE kpi_year_indicator SET code_type='$code_type', code_so='$code_so', code_side='$code_side', code_ap='$code_ap', weight_target='$weight_target', weight_owner='$weight_owner' WHERE code_kpi_year='$code_kpi_year' ");
				$sql = mysqli_query($db_KPI, "INSERT INTO kpi_year_indicator(code_kpi_year,code_type,code_so,code_side,code_ap,weight_target,weight_owner,status)
				VALUE('$code_kpi_year','$code_type','$code_so','$code_side','$code_ap','$weight_target','$weight_owner','$status')");
			}



			if ($sql) {
				// echo "U";
				$link = "?page=" . $page . '&op=kpi&years_select=' . $years_select;


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
				$link = "?page=" . $page . '&op=' . $op_from . '&years_select=' . $years_select . '&kpi_year_id=' . $kpi_year_id;

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
	}
	?>



</body>
<script>
	$(document).ready(function() {
		var table = $('#table_indicator_KPI').DataTable({
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
				}
			},
			pageLength: 10,
			order: [
				[1, 'asc']
			], // เรียงตามคอลัมน์ "ข้อมูลตัวชี้วัด"
			columnDefs: [{
					orderable: false,
					targets: [0, 2]
				} // ปิด sort สำหรับ "ลำดับที่" และ "จัดการข้อมูล"
			]
		});

		// ✅ อัปเดตลำดับอัตโนมัติเมื่อมีการ sort / search / เปลี่ยนหน้า
		table.on('order.dt search.dt draw.dt', function() {
			table.column(0, {
					search: 'applied',
					order: 'applied'
				})
				.nodes()
				.each(function(cell, i) {
					cell.innerHTML = i + 1;
				});
		}).draw();
	});
</script>
<?php
session_start();
include("../../../config/connect_db.php");
include("../fn/main_kpi.php");

$page = $_GET['page'];
$op = $_GET['op'];

?>



<body>

	<?php function set_mark()
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
							<i class="fas fa-cog text-blue"></i> กำหนดเครื่องหมาย
						</h5>
						<div class="card-toolbar align-self-center no-border">
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-mark" role="button" data-toggle="modal">
									<i class="fas fa-plus"></i> เพิ่มข้อมูล
								</a>
							</div>
						</div>

						<div class="card-toolbar align-self-center">
							<a href="#" data-action="reload" class="card-toolbar-btn text-blue text-105">
								<i class="fas fa-sync-alt"></i>
							</a>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive-md">
							<table class="table table-border-x brc-secondary-l3 mb-0">
								<thead>
									<tr class="text-80 text-default-d3 bgc-default-l4">
										<th class="pl-2 pl-lg-4">
											ลำดับที่
										</th>
										<th>
											สัญญาลักษณ์
										</th>
										<th>
											ความหมาย
										</th>
										<th>
											สถานะการใช้งาน
										</th>

										<th>
											จัดการข้อมูล
										</th>

									</tr>
								</thead>

								<tbody>
									<?php
									$symbol_data = getSetSymbolList($db_KPI, $id);

									if (!empty($symbol_data)) :
										foreach ($symbol_data as $i => $symbol) :
											// $r_prefix = getPrefix($db_person, $staff['prefix_code']);
											// $r_position = getPosition($db_person, $staff['position_code']);
											// $r_position_dean = getPositionDean($db_person, $staff['position_dean']);
											// $r_department = getDepartment($db_person, $staff['dep_code']);
											// $r_job = getJob($db_person, $staff['job_code']);
											// $r_unit = getUnit($db_person, $staff['unit_code']);

											// $birth_dates = convertDateForDisplay($staff['birth_dates']);
											// $in_date = convertDateForDisplay($staff['in_date']);

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>

												<td class="text-dark-m2 text-85">
													<?php echo htmlspecialchars($symbol['symbol']); ?>
												</td>

												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($symbol['name']); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $symbol['id'] ?>" data-type="set_mark"
															<?php if ($symbol['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-mark<?= $symbol['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-mark<?= $symbol['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-mark<?= $symbol['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $symbol['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-mark<?= $symbol['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> กำหนดเครื่องหมาย
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
																				สัญญาลักษณ์
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="symbol" value="<?= $symbol['symbol']; ?>" required />
																			</div>
																		</div>
																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				ความหมาย
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $symbol['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $symbol['id']; ?>">

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
												<i class="fas fa-cog text-secondary"></i> กำหนดเครื่องหมาย
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
																		สัญญาลักษณ์
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="symbol" required />
																	</td>
																</tr>
																<tr>
																	<td>
																		<i class="fas fa-layer-group text-blue"></i>
																	</td>
																	<td class="text-95 text-600 text-secondary-d2">
																		ความหมาย
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required />
																	</td>
																</tr>

															</table>
														</div>
													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">

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
						</div><!-- /.table-responsive-md -->

					</div><!-- /.card-body -->
				</div>
			</div>
		</div>
	<?php }  //set mark
	?>
	<?php function set_unit()
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
							<i class="fas fa-cog text-blue"></i> กำหนดหน่วยนับ
						</h5>
						<div class="card-toolbar align-self-center no-border">
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-unit" role="button" data-toggle="modal">
									<i class="fas fa-plus"></i> เพิ่มข้อมูล
								</a>
							</div>
						</div>

						<div class="card-toolbar align-self-center">
							<a href="#" data-action="reload" class="card-toolbar-btn text-blue text-105">
								<i class="fas fa-sync-alt"></i>
							</a>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive-md">
							<table class="table table-border-x brc-secondary-l3 mb-0">
								<thead>
									<tr class="text-80 text-default-d3 bgc-default-l4">
										<th class="pl-2 pl-lg-4">
											ลำดับที่
										</th>

										<th>
											หน่วยนับ
										</th>
										<th>
											สถานะการใช้งาน
										</th>

										<th>
											จัดการข้อมูล
										</th>

									</tr>
								</thead>

								<tbody>
									<?php
									$unit_data = getSetUnitList($db_KPI, $id);

									if (!empty($unit_data)) :
										foreach ($unit_data as $i => $unit) :

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($unit['name']); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $unit['id'] ?>" data-type="set_unit"
															<?php if ($unit['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-unit<?= $unit['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-unit<?= $unit['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-unit<?= $unit['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $unit['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-unit<?= $unit['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> กำหนดหน่วยนับ
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


																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				ความหมาย
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $unit['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $unit['id']; ?>">

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

										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
										</tr>
									<?php endif; ?>

								</tbody>
							</table>
							<!-- เพิ่ม -->
							<div class="modal fade modal-lg" id="modal-add-set-unit" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-cog text-secondary"></i> หน่วยนับ
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
																		หน่วยนับ
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required />
																	</td>
																</tr>

															</table>
															<p class="text-85 text-secondary"> <b class="text-danger text-100">หน่วยนับ</b> เช่น...<br>
																ผลงาน, ระบบ, หลักสูตร, กระบวนวิชา, สาขาวิชา, ชุมชน, แห่ง, พื้นที่, ธุรกิจ, องค์กร, บริษัท, หรือ มาตรฐาน
																จำนวนบทความตีพิมพ์, ร้อยละ, มูลค่าเงิน (บาท/ล้านบาท), จำนวนคน, คะแนน EdPEx, CMU-EdPEx (Item), ระดับ HA, หรือ อันดับ</p>
														</div>
													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">

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
						</div><!-- /.table-responsive-md -->

					</div><!-- /.card-body -->
				</div>
			</div>
		</div>
	<?php }  //set unit
	?>
	<?php function set_count()
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
							<i class="fas fa-cog text-blue"></i> กำหนด การนับ
						</h5>
						<div class="card-toolbar align-self-center no-border">
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-count" role="button" data-toggle="modal">
									<i class="fas fa-plus"></i> เพิ่มข้อมูล
								</a>
							</div>
						</div>

						<div class="card-toolbar align-self-center">
							<a href="#" data-action="reload" class="card-toolbar-btn text-blue text-105">
								<i class="fas fa-sync-alt"></i>
							</a>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive-md">
							<table class="table table-border-x brc-secondary-l3 mb-0">
								<thead>
									<tr class="text-80 text-default-d3 bgc-default-l4">
										<th class="pl-2 pl-lg-4">
											ลำดับที่
										</th>

										<th>
											หน่วยนับ
										</th>
										<th>
											สถานะการใช้งาน
										</th>

										<th>
											จัดการข้อมูล
										</th>

									</tr>
								</thead>

								<tbody>
									<?php
									$count_data = getSetCountList($db_KPI, $id);

									if (!empty($count_data)) :
										foreach ($count_data as $i => $count) :

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($count['name']); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $count['id'] ?>" data-type="set_count"
															<?php if ($count['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-count<?= $count['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-count<?= $count['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-count<?= $count['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $count['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-count<?= $count['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> กำหนด การนับ
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


																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				การนับ
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $count['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $count['id']; ?>">

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

										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
										</tr>
									<?php endif; ?>

								</tbody>
							</table>
							<!-- เพิ่ม -->
							<div class="modal fade modal-lg" id="modal-add-set-count" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-cog text-secondary"></i> กำหนด หน่วยนับ
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
																		หน่วยนับ
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required />
																	</td>
																</tr>

															</table>
															<p class="text-85 text-secondary"> <b class="text-danger text-100">เช่น...</b>
																นับสะสม นับค่าสุดท้าย</p>
														</div>
													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">

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
						</div><!-- /.table-responsive-md -->

					</div><!-- /.card-body -->
				</div>
			</div>
		</div>
	<?php }  //set count
	?>
	<?php function set_report_round()
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
							<i class="fas fa-cog text-blue"></i> กำหนด รอบการรายงาน
						</h5>
						<div class="card-toolbar align-self-center no-border">
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-report_round" role="button" data-toggle="modal">
									<i class="fas fa-plus"></i> เพิ่มข้อมูล
								</a>
							</div>
						</div>

						<div class="card-toolbar align-self-center">
							<a href="#" data-action="reload" class="card-toolbar-btn text-blue text-105">
								<i class="fas fa-sync-alt"></i>
							</a>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive-md">
							<table class="table table-border-x brc-secondary-l3 mb-0">
								<thead>
									<tr class="text-80 text-default-d3 bgc-default-l4">
										<th class="pl-2 pl-lg-4">
											ลำดับที่
										</th>

										<th>
											หน่วยนับ
										</th>
										<th>
											สถานะการใช้งาน
										</th>

										<th>
											จัดการข้อมูล
										</th>

									</tr>
								</thead>

								<tbody>
									<?php
									$report_round_data = getSetReportRoundList($db_KPI, $id);

									if (!empty($report_round_data)) :
										foreach ($report_round_data as $i => $report_round) :

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($report_round['name']); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $report_round['id'] ?>" data-type="set_report_round"
															<?php if ($report_round['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-report_round<?= $report_round['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-report_round<?= $report_round['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-report_round<?= $report_round['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $report_round['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-report_round<?= $report_round['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> กำหนด รอบการรายงาน
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


																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				รอบการรายงาน
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $report_round['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $report_round['id']; ?>">

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

										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
										</tr>
									<?php endif; ?>

								</tbody>
							</table>
							<!-- เพิ่ม -->
							<div class="modal fade modal-lg" id="modal-add-set-report_round" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-cog text-secondary"></i> กำหนด รอบการรายงาน
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
																		รอบการรายงาน
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required />
																	</td>
																</tr>

															</table>

														</div>
													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">

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
						</div><!-- /.table-responsive-md -->

					</div><!-- /.card-body -->
				</div>
			</div>
		</div>
	<?php }  //set report_round
	?>
	<?php function set_type()
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
							<i class="fas fa-cog text-blue"></i> กำหนด ประเภทตัวชี้วัด
						</h5>
						<div class="card-toolbar align-self-center no-border">
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-type" role="button" data-toggle="modal">
									<i class="fas fa-plus"></i> เพิ่มข้อมูล
								</a>
							</div>
						</div>

						<div class="card-toolbar align-self-center">
							<a href="#" data-action="reload" class="card-toolbar-btn text-blue text-105">
								<i class="fas fa-sync-alt"></i>
							</a>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive-md">
							<table class="table table-border-x brc-secondary-l3 mb-0">
								<thead>
									<tr class="text-80 text-default-d3 bgc-default-l4">
										<th class="pl-2 pl-lg-4">
											ลำดับที่
										</th>

										<th>
											ประเภทตัวชี้วัด
										</th>
										<th>
											สถานะการใช้งาน
										</th>

										<th>
											จัดการข้อมูล
										</th>

									</tr>
								</thead>

								<tbody>
									<?php
									$type_data = getSetTypeList($db_KPI, $id);

									if (!empty($type_data)) :
										foreach ($type_data as $i => $type) :

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($type['name']); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $type['id'] ?>" data-type="set_type"
															<?php if ($type['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-type<?= $type['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-type<?= $type['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-type<?= $type['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $type['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-type<?= $type['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> กำหนด ประเภทตัวชี้วัด
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


																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				ประเภทตัวชี้วัด
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $type['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $type['id']; ?>">

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

										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
										</tr>
									<?php endif; ?>

								</tbody>
							</table>
							<!-- เพิ่ม -->
							<div class="modal fade modal-lg" id="modal-add-set-type" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-cog text-secondary"></i> กำหนด ประเภทตัวชี้วัด
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
																		ประเภทตัวชี้วัด
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required />
																	</td>
																</tr>

															</table>

														</div>
													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">

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
						</div><!-- /.table-responsive-md -->

					</div><!-- /.card-body -->
				</div>
			</div>
		</div>
	<?php }  //set type
	?>
	<?php function set_so()
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
							<i class="fas fa-cog text-blue"></i> กำหนด SO
						</h5>
						<div class="card-toolbar align-self-center no-border">
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-so" role="button" data-toggle="modal">
									<i class="fas fa-plus"></i> เพิ่มข้อมูล
								</a>
							</div>
						</div>

						<div class="card-toolbar align-self-center">
							<a href="#" data-action="reload" class="card-toolbar-btn text-blue text-105">
								<i class="fas fa-sync-alt"></i>
							</a>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive-md">
							<table class="table table-border-x brc-secondary-l3 mb-0">
								<thead>
									<tr class="text-80 text-default-d3 bgc-default-l4">
										<th class="pl-2 pl-lg-4">
											ลำดับที่
										</th>

										<th>
											SO
										</th>
										<th>
											สถานะการใช้งาน
										</th>

										<th>
											จัดการข้อมูล
										</th>

									</tr>
								</thead>

								<tbody>
									<?php
									$so_data = getSetSOList($db_KPI, $id);

									if (!empty($so_data)) :
										foreach ($so_data as $i => $so) :

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($so['name']); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $so['id'] ?>" data-type="set_so"
															<?php if ($so['status'] == '1') echo "checked"; ?> />
													</label>

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

												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-so<?= $so['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-so<?= $so['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-so<?= $so['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $so['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-so<?= $so['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> กำหนด SO
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


																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				SO
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $so['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $so['id']; ?>">

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

										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
										</tr>
									<?php endif; ?>

								</tbody>
							</table>
							<!-- เพิ่ม -->
							<div class="modal fade modal-lg" id="modal-add-set-so" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-cog text-secondary"></i> กำหนด SO
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
																		SO
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required />
																	</td>
																</tr>

															</table>

														</div>
													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">

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
						</div><!-- /.table-responsive-md -->

					</div><!-- /.card-body -->
				</div>
			</div>
		</div>
	<?php }  //set so
	?>
	<?php function set_ap()
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
							<i class="fas fa-cog text-blue"></i> กำหนด แผนปฏิบัติการ (AP)
						</h5>
						<div class="card-toolbar align-self-center no-border">
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-ap" role="button" data-toggle="modal">
									<i class="fas fa-plus"></i> เพิ่มข้อมูล
								</a>
							</div>
						</div>

						<div class="card-toolbar align-self-center">
							<a href="#" data-action="reload" class="card-toolbar-btn text-blue text-105">
								<i class="fas fa-sync-alt"></i>
							</a>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive-md">
							<table class="table table-border-x brc-secondary-l3 mb-0">
								<thead>
									<tr class="text-80 text-default-d3 bgc-default-l4">
										<th class="pl-2 pl-lg-4">
											ลำดับที่
										</th>

										<th>
											แผนปฏิบัติการ (AP)
										</th>
										<th>
											สถานะการใช้งาน
										</th>

										<th>
											จัดการข้อมูล
										</th>

									</tr>
								</thead>

								<tbody>
									<?php
									$ap_data = getSetAPList($db_KPI, $id);

									if (!empty($ap_data)) :
										foreach ($ap_data as $i => $ap) :

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($ap['name']); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $ap['id'] ?>" data-type="set_ap"
															<?php if ($ap['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-ap<?= $ap['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-ap<?= $ap['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-ap<?= $ap['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $ap['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-ap<?= $ap['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> กำหนด แผนปฏิบัติการ (AP)
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


																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				แผนปฏิบัติการ (AP)
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $ap['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $ap['id']; ?>">

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

										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
										</tr>
									<?php endif; ?>

								</tbody>
							</table>
							<!-- เพิ่ม -->
							<div class="modal fade modal-lg" id="modal-add-set-ap" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-cog text-secondary"></i> กำหนดแผนปฏิบัติการ (AP)
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
																		แผนปฏิบัติการ (AP)
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required />
																	</td>
																</tr>

															</table>

														</div>
													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">

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
						</div><!-- /.table-responsive-md -->

					</div><!-- /.card-body -->
				</div>
			</div>
		</div>
	<?php }  //set AP
	?>
	<?php function set_side()
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
							<i class="fas fa-cog text-blue"></i> กำหนด ด้าน
						</h5>
						<div class="card-toolbar align-self-center no-border">
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-side" role="button" data-toggle="modal">
									<i class="fas fa-plus"></i> เพิ่มข้อมูล
								</a>
							</div>
						</div>

						<div class="card-toolbar align-self-center">
							<a href="#" data-action="reload" class="card-toolbar-btn text-blue text-105">
								<i class="fas fa-sync-alt"></i>
							</a>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive-md">
							<table class="table table-border-x brc-secondary-l3 mb-0">
								<thead>
									<tr class="text-80 text-default-d3 bgc-default-l4">
										<th class="pl-2 pl-lg-4">
											ลำดับที่
										</th>

										<th>
											ด้าน
										</th>
										<th>
											สถานะการใช้งาน
										</th>

										<th>
											จัดการข้อมูล
										</th>

									</tr>
								</thead>

								<tbody>
									<?php
									$side_data = getSetSideList($db_KPI, $id);

									if (!empty($side_data)) :
										foreach ($side_data as $i => $side) :

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($side['name']); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $side['id'] ?>" data-type="set_side"
															<?php if ($side['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-side<?= $side['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-side<?= $side['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-side<?= $side['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $side['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-side<?= $side['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> กำหนด ด้าน
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


																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				ด้าน
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $side['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $side['id']; ?>">

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

										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
										</tr>
									<?php endif; ?>

								</tbody>
							</table>
							<!-- เพิ่ม -->
							<div class="modal fade modal-lg" id="modal-add-set-side" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-cog text-secondary"></i> กำหนด ด้าน
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
																		ด้าน
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required />
																	</td>
																</tr>

															</table>

														</div>
													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">

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
						</div><!-- /.table-responsive-md -->

					</div><!-- /.card-body -->
				</div>
			</div>
		</div>
	<?php }  //set side
	?>
	<?php function set_tag()
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
							<i class="fas fa-cog text-blue"></i> กำหนด Tag
						</h5>
						<div class="card-toolbar align-self-center no-border">
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-tag" role="button" data-toggle="modal">
									<i class="fas fa-plus"></i> เพิ่มข้อมูล
								</a>
							</div>
						</div>

						<div class="card-toolbar align-self-center">
							<a href="#" data-action="reload" class="card-toolbar-btn text-blue text-105">
								<i class="fas fa-sync-alt"></i>
							</a>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive-md">
							<table class="table table-border-x brc-secondary-l3 mb-0">
								<thead>
									<tr class="text-80 text-default-d3 bgc-default-l4">
										<th class="pl-2 pl-lg-4">
											ลำดับที่
										</th>

										<th>
											Tag
										</th>
										<th>
											สถานะการใช้งาน
										</th>

										<th>
											จัดการข้อมูล
										</th>

									</tr>
								</thead>

								<tbody>
									<?php
									$tag_data = getSetTagList($db_KPI, $id);

									if (!empty($tag_data)) :
										foreach ($tag_data as $i => $tag) :

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($tag['name']); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $tag['id'] ?>" data-type="set_tag"
															<?php if ($tag['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-tag<?= $tag['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-tag<?= $tag['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-tag<?= $tag['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $tag['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-tag<?= $tag['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> กำหนด Tag
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


																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				Tag
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $tag['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $tag['id']; ?>">

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

										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
										</tr>
									<?php endif; ?>

								</tbody>
							</table>
							<!-- เพิ่ม -->
							<div class="modal fade modal-lg" id="modal-add-set-tag" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-cog text-secondary"></i> Tag
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
																		Tag
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required />
																	</td>
																</tr>

															</table>

														</div>
													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">

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
						</div><!-- /.table-responsive-md -->

					</div><!-- /.card-body -->
				</div>
			</div>
		</div>
	<?php }  //set tag
	?>
	<?php function set_owner()
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
							<i class="fas fa-cog text-blue"></i> กำหนด data owner/kpi owner
						</h5>
						<div class="card-toolbar align-self-center no-border">
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-owner" role="button" data-toggle="modal">
									<i class="fas fa-plus"></i> เพิ่มข้อมูล
								</a>
							</div>
						</div>

						<div class="card-toolbar align-self-center">
							<a href="#" data-action="reload" class="card-toolbar-btn text-blue text-105">
								<i class="fas fa-sync-alt"></i>
							</a>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive-md">
							<table class="table table-border-x brc-secondary-l3 mb-0">
								<thead>
									<tr class="text-80 text-default-d3 bgc-default-l4">
										<th class="pl-2 pl-lg-4">
											ลำดับที่
										</th>

										<th>
											รายละเอียด
										</th>
										<th>
											ประเภท
										</th>
										<th>
											สถานะการใช้งาน
										</th>

										<th>
											จัดการข้อมูล
										</th>

									</tr>
								</thead>

								<tbody>
									<?php
									$owner_data = getSetOwnerList($db_KPI, $id);

									if (!empty($owner_data)) :
										foreach ($owner_data as $i => $owner) :
											if ($owner['type'] == '1') {
												$type_name = 'data owner';
											} else if ($owner['type'] == '2') {
												$type_name = 'kpi owner';
											}

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($owner['name']); ?>
												</td>
												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($type_name); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $owner['id'] ?>" data-type="set_owner"
															<?php if ($owner['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-owner<?= $owner['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-owner<?= $owner['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-owner<?= $owner['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $owner['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-owner<?= $owner['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> กำหนด data owner/kpi owner
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


																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				รายละเอียด
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $owner['name']; ?>" required />

																			</div>
																		</div>
																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				ประเภท
																			</div>
																			<div class=col-md-8>
																				<input type="radio" name="type" class="mr-1" data-id="<?= $owner['id'] ?>" value="1" <?php if ($owner['type'] == '1') echo "checked"; ?> /> data owner
																				<input type="radio" name="type" class="mr-1" data-id="<?= $owner['id'] ?>" value="2" <?php if ($owner['type'] == '2') echo "checked"; ?> /> kpi owner



																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $owner['id']; ?>">

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

										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
										</tr>
									<?php endif; ?>

								</tbody>
							</table>
							<!-- เพิ่ม -->
							<div class="modal fade modal-lg" id="modal-add-set-owner" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-cog text-secondary"></i> รายละเอียด
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
																		รายละเอียด
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required />
																	</td>
																</tr>
																<tr>
																	<td>
																		<i class="fas fa-layer-group text-blue"></i>
																	</td>
																	<td class="text-95 text-600 text-secondary-d2">
																		ประเภท
																	</td>
																	<td class="text-dark-m3">
																		<input type="radio" name="type" class="mr-1" data-id="<?= $owner['id'] ?>" value="1" Checked /> data owner
																		<input type="radio" name="type" class="mr-1" data-id="<?= $owner['id'] ?>" value="2" /> kpi owner

																	</td>
																</tr>

															</table>

														</div>
													</div>
													<input type="hidden" name="op_from" value="<?= $op_from; ?>">

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
						</div><!-- /.table-responsive-md -->

					</div><!-- /.card-body -->
				</div>
			</div>
		</div>
	<?php }  //set owner
	?>
	<?php
	switch ($op) {
		case "set_mark":
			set_mark();
			break;
		case "set_unit":
			set_unit();
			break;
		case "set_so":
			set_so();
			break;
		case "set_tag":
			set_tag();
			break;
		case "set_count":
			set_count();
			break;
		case "set_side":
			set_side();
			break;
		case "set_type":
			set_type();
			break;
		case "set_owner":
			set_owner();
			break;
		case "set_report_round":
			set_report_round();
			break;
		case "set_ap":
			set_ap();
			break;
		case "save_data":

			include("../../../config/connect_db.php");
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$status = '1';
			$op_from = $_POST['op_from'];
			if ($op_from == "set_mark") {
				$symbol = $_POST['symbol'];
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "INSERT INTO set_symbol(symbol,name,status)VALUE('$symbol','$name','$status')");
			} elseif ($op_from == "set_unit") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "INSERT INTO set_unit(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_so") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "INSERT INTO set_so(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_tag") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "INSERT INTO set_tag(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_count") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "INSERT INTO set_count(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_side") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "INSERT INTO set_side(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_type") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "INSERT INTO set_type(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_owner") {
				$name = $_POST['name'];
				$type = $_POST['type'];
				$sql = mysqli_query($db_KPI, "INSERT INTO set_owner(name,status,type)VALUE('$name','$status','$type')");
			} elseif ($op_from == "set_report_round") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "INSERT INTO set_report_round(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_ap") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "INSERT INTO set_ap(name,status)VALUE('$name','$status')");
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

			if ($op_from == "set_mark") {
				$symbol = $_POST['symbol'];
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "UPDATE set_symbol SET symbol='$symbol',name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_unit") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "UPDATE set_unit SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_so") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "UPDATE set_so SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_tag") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "UPDATE set_tag SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_count") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "UPDATE set_count SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_side") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "UPDATE set_side SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_type") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "UPDATE set_type SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_owner") {
				$name = $_POST['name'];
				$type = $_POST['type'];
				$sql = mysqli_query($db_KPI, "UPDATE set_owner SET name='$name',type='$type' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_report_round") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "UPDATE set_report_round SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_ap") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_KPI, "UPDATE set_ap SET name='$name' WHERE id='$_POST[id]' ");
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
	}
	?>



</body>
<!-- แบบที่ 1 -->
<!-- <script>
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
</script> -->
<!-- แบบที่ 2 -->

<script>
	$(document).on('change', '.ace-switch', function() {
		let id = $(this).data('id');
		let type = $(this).data('type');
		let status = $(this).is(':checked') ? 1 : 0;
		let switchElem = $(this); // เก็บ element ปัจจุบันไว้ใช้ย้อนกลับ

		$.ajax({
			url: "update_status.php",
			type: "POST",
			data: {
				id: id,
				status: status,
				type: type
			},
			success: function(response) {
				response = response.trim();

				if (response === "success") {
					// ✅ แสดงข้อความแบบเรียลไทม์ (Toast)
					showToast("บันทึกเรียบร้อยแล้ว ✅", "success");
				} else if (response === "no_change") {
					showToast("ไม่มีการเปลี่ยนแปลง", "info");
				} else {
					showToast("เกิดข้อผิดพลาด: " + response, "error");
					switchElem.prop('checked', !status); // ❌ ย้อนกลับสถานะถ้า error
				}
			},
			error: function(xhr, status, error) {
				console.error("AJAX Error:", error);
				showToast("การเชื่อมต่อผิดพลาด ⚠️", "error");
				switchElem.prop('checked', !status); // ❌ ย้อนกลับสถานะ
			}
		});
	});

	// ✅ ฟังก์ชันแสดง Toast (แจ้งเตือนแบบเรียลไทม์)
	function showToast(message, type = "info") {
		const bgColor = {
			success: "#28a745",
			info: "#17a2b8",
			error: "#dc3545"
		} [type] || "#6c757d";

		let toast = $('<div>')
			.text(message)
			.css({
				position: "fixed",
				bottom: "30px",
				right: "30px",
				background: bgColor,
				color: "#fff",
				padding: "10px 20px",
				borderRadius: "8px",
				boxShadow: "0 2px 6px rgba(0,0,0,0.3)",
				zIndex: 9999,
				fontSize: "14px"
			})
			.hide()
			.appendTo("body")
			.fadeIn(200);

		setTimeout(() => toast.fadeOut(400, () => toast.remove()), 2000);
	}
</script>
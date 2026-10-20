<?php
// ถูก include จาก page.php ที่ start session แล้ว — ไม่เรียก session_start()
include_once(__DIR__ . '/../../config/config_db.php');
include_once(__DIR__ . '/../fn/main_project.php');
try {
	ensureSetProjectPersonnelTable($db_Project);
} catch (Throwable $e) {
	@error_log('setting.php ensureSetProjectPersonnelTable: ' . $e->getMessage());
}

$page = isset($_GET['page']) ? trim((string)$_GET['page']) : '';
$op   = isset($_GET['op']) ? trim((string)$_GET['op']) : '';
if ($op === '' && isset($_GET['cp'])) {
	$op = trim((string) $_GET['cp']);
}
?>

	<?php function set_Project_Type()
	{
		global $db_Project, $db_person;
		$page = $_GET['page'];
		$op_from = $_GET['op'];
	?>
		<style>
			.unit-paper-badge {
				display: inline-block;
				padding: 0.32rem 0.75rem;
				border-radius: 999px;
				background-color: #926cb8;
				color: #fff;
				font-size: 0.8rem;
				font-weight: 700;
				letter-spacing: 0.2px;
				box-shadow: 0 1px 6px rgba(146, 108, 184, 0.35);
				vertical-align: middle;
			}
		</style>
		<div class="row mt-4">
			<!-- orders table -->
			<div class="col-12 col-lg-12">
				<div class="bcard card h-100 pt-1 pt-lg-2">

					<div class="card-header border-0">
						<h5 class="card-title text-dark-m2 pl-1 text-110">
							<i class="fas fa-cog text-blue"></i> ประเภทโครงการ
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
									$ProjectType_data = getSetProjectTypeList($db_Project, $id);

									if (!empty($ProjectType_data)) :
										foreach ($ProjectType_data as $i => $ProjectType) :


									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($ProjectType['name']); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $ProjectType['id'] ?>" data-type="set_Project_Type"
															<?php if ($ProjectType['status'] == '1') echo "checked"; ?> />
													</label>


												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-mark<?= $ProjectType['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-mark<?= $ProjectType['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-mark<?= $ProjectType['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $ProjectType['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<input type="hidden" name="tb_data" value="set_project_type">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-mark<?= $ProjectType['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> ประเภทโครงการ
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
																				ชื่อประเภท
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $ProjectType['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $ProjectType['id']; ?>">

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
												<i class="fas fa-cog text-secondary"></i> ประเภทโครงการ
											</h5>

											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>

										</div>

										<div class="modal-body bg-white">
											<div class="bgc-white radius-1">
												<form class="row col-12 needs-validation" id="form-add-unit-ajax" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data" method="POST">

													<div class="row col-12 mt-2 mt-md-4">
														<!-- the left side profile picture and other info -->

														<div class="col-md-12">
															<table class="table table table-striped-default table-borderless">

																<tr>
																	<td>
																		<i class="fas fa-layer-group text-blue"></i>
																	</td>
																	<td class="text-95 text-600 text-secondary-d2">
																		ชื่อประเภท
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
	<?php function set_project_nature()
	{
		global $db_Project, $db_person;
		$page = $_GET['page'];
		$op_from = $_GET['op'];
	?>
		<div class="row mt-4">
			<!-- orders table -->
			<div class="col-12 col-lg-12">
				<div class="bcard card h-100 pt-1 pt-lg-2">

					<div class="card-header border-0">
						<h5 class="card-title text-dark-m2 pl-1 text-110">
							<i class="fas fa-cog text-blue"></i> ลักษณะโครงการ
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
											รายการ
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
									$nature_data = getSetNatureList($db_Project, $id);

									if (!empty($nature_data)) :
										foreach ($nature_data as $i => $nature) :

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($nature['name']); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $nature['id'] ?>" data-type="set_nature"
															<?php if ($nature['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-unit<?= $nature['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-unit<?= $nature['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-unit<?= $nature['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $nature['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<input type="hidden" name="tb_data" value="set_project_nature">

																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-unit<?= $nature['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> ลักษณะโครงการ
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
																				ลักษณะโครงการ
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $nature['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $nature['id']; ?>">

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
												<i class="fas fa-cog text-secondary"></i> ลักษณะโครงการ
											</h5>

											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>

										</div>

										<div class="modal-body bg-white">
											<div class="bgc-white radius-1">
												<form class="row col-12 needs-validation" id="form-add-unit-ajax" data-unit-form="1" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data" method="POST">

													<div class="row col-12 mt-2 mt-md-4">
														<!-- the left side profile picture and other info -->

														<div class="col-md-12">
															<table class="table table table-striped-default table-borderless">

																<tr>
																	<td>
																		<i class="fas fa-layer-group text-blue"></i>
																	</td>
																	<td class="text-95 text-600 text-secondary-d2">
																		ลักษณะโครงการ
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
	<?php }  //set unit
	?>
	<?php function set_project_budget()
	{
		global $db_Project, $db_pbmBudget_PBM;
		$budgetDb = (isset($db_pbmBudget_PBM) && $db_pbmBudget_PBM instanceof mysqli) ? $db_pbmBudget_PBM : $db_Project;
		$budget_data = function_exists('getPbmBudgetManageList')
			? getPbmBudgetManageList($budgetDb, true)
			: getSetBudgetList($budgetDb);

		$budget_by_year = [];
		$year_options = [];
		$totalAll = 0.0;
		foreach ($budget_data as $budget) {
			$yr = trim((string)($budget['year'] ?? ''));
			if ($yr === '') {
				$yr = '-';
			}
			if (!isset($budget_by_year[$yr])) {
				$budget_by_year[$yr] = [];
			}
			$budget_by_year[$yr][] = $budget;
			$totalAll += (float)($budget['amount'] ?? 0);
			if ($yr !== '-') {
				$year_options[$yr] = $yr;
			}
		}
		krsort($budget_by_year, SORT_STRING);
		krsort($year_options, SORT_STRING);
		$totalCount = count($budget_data);
	?>
		<style>
			.pm-budget-src-wrap{
				--pbs-ink:#0f2744;
				--pbs-muted:#64748b;
				--pbs-line:#e8eef6;
				--pbs-soft:#f4f8fc;
				--pbs-accent:#926cb8;
				--pbs-accent-soft:#f3eef8;
				--pbs-money:#0f766e;
			}
			.pm-budget-src-card{
				border:0 !important;
				border-radius:1rem !important;
				overflow:hidden;
				box-shadow: 0 10px 28px -18px rgba(15,39,68,.35);
				background:#fff;
			}
			.pm-budget-src-head{
				padding:1.05rem 1.2rem .95rem;
				background:
					radial-gradient(1200px 180px at 0% 0%, rgba(146,108,184,.10), transparent 55%),
					linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
				border-bottom:1px solid var(--pbs-line);
			}
			.pm-budget-src-title{
				display:flex;
				align-items:flex-start;
				gap:.75rem;
				margin:0;
				color:var(--pbs-ink);
				font-weight:900;
				font-size:1.15rem;
				line-height:1.25;
			}
			.pm-budget-src-title-ico{
				width:2.25rem; height:2.25rem;
				border-radius:.75rem;
				display:inline-flex; align-items:center; justify-content:center;
				background:linear-gradient(135deg,#926cb8,#734d96);
				color:#fff;
				box-shadow:0 10px 18px -10px rgba(146,108,184,.7);
				flex:0 0 auto;
			}
			.pm-budget-src-sub{
				display:block;
				margin-top:.2rem;
				font-size:.82rem;
				font-weight:600;
				color:var(--pbs-muted);
			}
			.pm-budget-src-stats{
				display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.75rem;
			}
			.pm-budget-src-stat{
				display:inline-flex; align-items:center; gap:.35rem;
				padding:.28rem .7rem;
				border-radius:999px;
				background:#fff;
				border:1px solid var(--pbs-line);
				color:var(--pbs-ink);
				font-size:.78rem;
				font-weight:800;
			}
			.pm-budget-src-stat i{ color:var(--pbs-accent); }
			.pm-budget-src-toolbar{
				display:flex; align-items:center; gap:.55rem; flex-wrap:wrap;
				margin-top:.85rem;
			}
			.pm-budget-src-year,
			.pm-budget-src-search{
				border-radius:.7rem !important;
				border:1px solid #d7e3f2 !important;
				background:#fff !important;
				min-height:2.15rem;
			}
			.pm-budget-src-year{ max-width:170px; min-width:140px; }
			.pm-budget-src-search{ max-width:340px; min-width:220px; flex:1 1 220px; }
			.pm-budget-src-refresh{
				width:2.15rem; height:2.15rem;
				border-radius:.7rem;
				display:inline-flex; align-items:center; justify-content:center;
				border:1px solid #d7e3f2;
				background:#fff;
				color:var(--pbs-accent);
				text-decoration:none !important;
			}
			.pm-budget-src-refresh:hover{ background:var(--pbs-accent-soft); }
			.pm-budget-src-table{
				margin:0;
				background:#fff;
			}
			.pm-budget-src-table thead th{
				position:sticky; top:0; z-index:2;
				background:#f8fbff !important;
				color:#334155 !important;
				font-weight:800;
				font-size:.78rem;
				letter-spacing:.02em;
				text-transform:uppercase;
				border-top:0 !important;
				border-bottom:1px solid var(--pbs-line) !important;
				padding:.7rem 1rem !important;
				vertical-align:middle;
				white-space:nowrap;
			}
			.pm-budget-src-table tbody td{
				color:var(--pbs-ink);
				border-top:0 !important;
				border-bottom:1px solid #eef3f8 !important;
				padding:.85rem 1rem !important;
				vertical-align:top;
				background:#fff;
			}
			.pm-budget-src-table tbody tr.pm-budget-src-data:hover td{
				background:#faf7fc !important;
			}
			.pm-budget-src-table tbody tr.pm-budget-year-row td{
				background: linear-gradient(90deg, #f3eef8 0%, #faf7fc 100%) !important;
				border-bottom:1px solid #e8def2 !important;
				padding:.7rem 1rem !important;
			}
			.pm-budget-year-banner{
				display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap;
			}
			.pm-budget-year-left{
				display:flex; align-items:center; gap:.55rem;
				color:#4a3263; font-weight:900; font-size:.95rem;
			}
			.pm-budget-year-left .pm-budget-year-dot{
				width:1.85rem; height:1.85rem; border-radius:.55rem;
				display:inline-flex; align-items:center; justify-content:center;
				background:#fff; border:1px solid #e8def2; color:#734d96;
			}
			.pm-budget-year-meta{
				display:flex; flex-wrap:wrap; gap:.4rem;
			}
			.pm-budget-year-chip{
				display:inline-flex; align-items:center;
				padding:.2rem .55rem;
				border-radius:999px;
				background:#fff;
				border:1px solid #d5e4f7;
				color:#334155;
				font-size:.75rem;
				font-weight:800;
			}
			.pm-budget-year-chip.is-money{
				color:var(--pbs-money);
				border-color:#b7ebe3;
				background:#f0fdfa;
			}
			.pm-budget-src-code{
				display:inline-flex;
				align-items:center;
				padding:.28rem .55rem;
				border-radius:.5rem;
				background:#f1f5f9;
				border:1px solid #e2e8f0;
				color:#0f172a;
				font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
				font-weight:800;
				font-size:.82rem;
				letter-spacing:.01em;
				white-space:nowrap;
			}
			.pm-budget-src-name{
				font-size:.86rem;
				line-height:1.45;
				font-weight:600;
				color:#1e293b;
			}
			.pm-budget-src-name-text{
				display:-webkit-box;
				-webkit-line-clamp:2;
				-webkit-box-orient:vertical;
				overflow:hidden;
			}
			.pm-budget-src-data.is-expanded .pm-budget-src-name-text{
				display:block;
				-webkit-line-clamp:unset;
				overflow:visible;
			}
			.pm-budget-src-path{
				display:flex; flex-wrap:wrap; gap:.28rem; margin-top:.4rem;
			}
			.pm-budget-src-seg{
				display:inline-flex; align-items:center;
				max-width:100%;
				padding:.12rem .45rem;
				border-radius:.4rem;
				background:var(--pbs-soft);
				border:1px solid #e6eef8;
				color:#475569;
				font-size:.72rem;
				font-weight:700;
				line-height:1.3;
			}
			.pm-budget-src-toggle{
				border:0;
				background:transparent;
				color:var(--pbs-accent);
				font-size:.75rem;
				font-weight:800;
				padding:0;
				margin-top:.35rem;
				cursor:pointer;
			}
			.pm-budget-src-toggle:hover{ text-decoration:underline; }
			.pm-budget-src-amount{
				font-weight:900;
				font-variant-numeric: tabular-nums;
				white-space:nowrap;
				text-align:right;
				color:var(--pbs-money);
				font-size:.95rem;
			}
			.pm-budget-src-amount small{
				display:block;
				font-size:.68rem;
				font-weight:700;
				color:#94a3b8;
				margin-top:.1rem;
			}
			.pm-budget-src-empty{
				text-align:center;
				padding:2.5rem 1rem !important;
				color:var(--pbs-muted);
				font-weight:700;
			}
			.pm-budget-src-foot{
				display:flex;
				align-items:center;
				justify-content:space-between;
				gap:.75rem;
				flex-wrap:wrap;
				padding:.85rem 1.1rem;
				border-top:1px solid var(--pbs-line);
				background:linear-gradient(180deg,#fbfdff 0%, #f5f9fd 100%);
			}
			.pm-budget-src-summary{
				font-size:.84rem;
				font-weight:700;
				color:var(--pbs-muted);
			}
			.pm-budget-src-pager{
				display:flex;
				align-items:center;
				gap:.35rem;
				flex-wrap:wrap;
			}
			.pm-budget-src-pager .btn{
				min-width:2.1rem;
				height:2.1rem;
				padding:.2rem .45rem;
				border-radius:.55rem;
				font-weight:800;
				font-size:.82rem;
				line-height:1.1;
			}
			.pm-budget-src-pager .btn.active{
				background:linear-gradient(135deg,#926cb8,#734d96);
				border-color:transparent;
				color:#fff;
				box-shadow:0 8px 14px -10px rgba(146,108,184,.8);
			}
			.pm-budget-src-perpage{
				max-width:110px;
				min-width:90px;
				border-radius:.55rem !important;
				border:1px solid #d7e3f2 !important;
				min-height:2.1rem;
				font-weight:700;
				font-size:.82rem;
			}
			@media (max-width: 767.98px){
				.pm-budget-src-table thead th:nth-child(1),
				.pm-budget-src-table tbody td:nth-child(1){ width:34%; }
				.pm-budget-src-code{ white-space:normal; word-break:break-all; }
			}
		</style>
		<div class="row mt-4 pm-budget-src-wrap">
			<div class="col-12">
				<div class="card pm-budget-src-card">
					<div class="pm-budget-src-head">
						<div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:1rem;">
							<div>
								<div class="pm-budget-src-title">
									<span class="pm-budget-src-title-ico"><i class="fas fa-coins"></i></span>
									<span>
										แหล่งงบประมาณ
										<span class="pm-budget-src-sub">ข้อมูลจากระบบ PBM Budget · จัดกลุ่มตามปีงบประมาณ</span>
									</span>
								</div>
								<div class="pm-budget-src-stats">
									<span class="pm-budget-src-stat"><i class="fas fa-list"></i> <?= (int)$totalCount ?> รายการ</span>
									<span class="pm-budget-src-stat"><i class="fas fa-calendar"></i> <?= count($year_options) ?> ปีงบประมาณ</span>
									<span class="pm-budget-src-stat"><i class="fas fa-coins"></i> รวม <?= number_format($totalAll, 2) ?> บาท</span>
								</div>
							</div>
						</div>
						<div class="pm-budget-src-toolbar">
							<select id="pmBudgetSrcYear" class="form-control form-control-sm pm-budget-src-year" title="ปีงบประมาณ">
								<option value="">ทุกปีงบประมาณ</option>
								<?php foreach ($year_options as $yo): ?>
									<option value="<?= htmlspecialchars($yo, ENT_QUOTES, 'UTF-8'); ?>">ปี <?= htmlspecialchars($yo, ENT_QUOTES, 'UTF-8'); ?></option>
								<?php endforeach; ?>
							</select>
							<input type="search" id="pmBudgetSrcSearch" class="form-control form-control-sm pm-budget-src-search" placeholder="ค้นหารหัสงบ / ชื่อแหล่ง">
							<a href="?page=setting&op=set_project_budget" class="pm-budget-src-refresh" title="รีเฟรช">
								<i class="fas fa-sync-alt"></i>
							</a>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive-md">
							<table class="table mb-0 pm-budget-src-table" id="pmBudgetSrcTable">
								<thead>
									<tr>
										<th style="width:17%;">รหัสงบ</th>
										<th>ชื่อแหล่ง</th>
										<th style="width:15%;" class="text-right">จำนวนเงิน</th>
									</tr>
								</thead>
								<tbody>
								<?php if (!empty($budget_by_year)) : ?>
									<?php foreach ($budget_by_year as $yrLabel => $rows) :
										$yearSum = 0.0;
										foreach ($rows as $__b) {
											$yearSum += (float)($__b['amount'] ?? 0);
										}
									?>
									<tr class="pm-budget-year-row" data-budget-year="<?= htmlspecialchars((string)$yrLabel, ENT_QUOTES, 'UTF-8'); ?>">
										<td colspan="3">
											<div class="pm-budget-year-banner">
												<div class="pm-budget-year-left">
													<span class="pm-budget-year-dot"><i class="fas fa-calendar-alt"></i></span>
													<span>ปีงบประมาณ <?= htmlspecialchars($yrLabel === '-' ? 'ไม่ระบุ' : (string)$yrLabel, ENT_QUOTES, 'UTF-8'); ?></span>
												</div>
												<div class="pm-budget-year-meta">
													<span class="pm-budget-year-chip"><?= count($rows) ?> รายการ</span>
													<span class="pm-budget-year-chip is-money">รวม <?= number_format($yearSum, 2) ?> บาท</span>
												</div>
											</div>
										</td>
									</tr>
									<?php foreach ($rows as $budget) :
										$code = trim((string)($budget['code'] ?? ($budget['id'] ?? '')));
										$name = trim((string)($budget['name'] ?? ''));
										$amount = (float)($budget['amount'] ?? 0);
										$search = function_exists('mb_strtolower')
											? mb_strtolower($code . ' ' . $name . ' ' . $yrLabel, 'UTF-8')
											: strtolower($code . ' ' . $name . ' ' . $yrLabel);
										$needToggle = function_exists('mb_strlen') ? (mb_strlen($name, 'UTF-8') > 110) : (strlen($name) > 110);
									?>
									<tr class="pm-budget-src-data"
										data-budget-year="<?= htmlspecialchars((string)$yrLabel, ENT_QUOTES, 'UTF-8'); ?>"
										data-budget-search="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
										<td>
											<span class="pm-budget-src-code"><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?></span>
										</td>
										<td class="pm-budget-src-name">
											<div class="pm-budget-src-name-text" title="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
												<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
											</div>
											<?php if ($needToggle): ?>
												<button type="button" class="pm-budget-src-toggle" data-action="toggle-budget-name">แสดงทั้งหมด</button>
											<?php endif; ?>
										</td>
										<td class="pm-budget-src-amount">
											<?= number_format($amount, 2) ?>
											<small>บาท</small>
										</td>
									</tr>
									<?php endforeach; ?>
									<?php endforeach; ?>
								<?php else : ?>
									<tr>
										<td colspan="3" class="pm-budget-src-empty">ไม่พบข้อมูลแหล่งงบจาก PBM</td>
									</tr>
								<?php endif; ?>
								</tbody>
							</table>
						</div>
						<div class="pm-budget-src-foot">
							<div class="d-flex align-items-center" style="gap:.55rem;flex-wrap:wrap;">
								<span class="pm-budget-src-summary" id="pmBudgetSrcSummary">-</span>
								<select id="pmBudgetSrcPerPage" class="form-control form-control-sm pm-budget-src-perpage" title="จำนวนต่อหน้า">
									<option value="10">10 / หน้า</option>
									<option value="20" selected>20 / หน้า</option>
									<option value="50">50 / หน้า</option>
								</select>
							</div>
							<div class="pm-budget-src-pager" id="pmBudgetSrcPager"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script>
		(function(){
			var inp = document.getElementById('pmBudgetSrcSearch');
			var yearSel = document.getElementById('pmBudgetSrcYear');
			var perPageSel = document.getElementById('pmBudgetSrcPerPage');
			var table = document.getElementById('pmBudgetSrcTable');
			var pager = document.getElementById('pmBudgetSrcPager');
			var summary = document.getElementById('pmBudgetSrcSummary');
			if (!table) return;
			var currentPage = 1;

			function getFilteredDataRows(){
				var q = String((inp && inp.value) || '').toLowerCase().trim();
				var yr = String((yearSel && yearSel.value) || '').trim();
				var dataRows = table.querySelectorAll('tbody tr.pm-budget-src-data');
				var matched = [];
				for (var i = 0; i < dataRows.length; i++) {
					var row = dataRows[i];
					var hay = row.getAttribute('data-budget-search') || '';
					var rowYear = row.getAttribute('data-budget-year') || '';
					var okYear = !yr || rowYear === yr;
					var okQ = !q || hay.indexOf(q) !== -1;
					if (okYear && okQ) matched.push(row);
				}
				return matched;
			}

			function renderPager(totalPages){
				if (!pager) return;
				pager.innerHTML = '';
				if (totalPages <= 1) return;
				function addBtn(label, page, disabled, active){
					var b = document.createElement('button');
					b.type = 'button';
					b.className = 'btn btn-sm ' + (active ? 'btn-primary active' : 'btn-outline-secondary');
					b.textContent = label;
					b.disabled = !!disabled;
					b.addEventListener('click', function(){
						if (disabled || page === currentPage) return;
						currentPage = page;
						applyFilter(false);
					});
					pager.appendChild(b);
				}
				addBtn('‹', Math.max(1, currentPage - 1), currentPage <= 1, false);
				var start = Math.max(1, currentPage - 2);
				var end = Math.min(totalPages, start + 4);
				start = Math.max(1, end - 4);
				for (var p = start; p <= end; p++) {
					addBtn(String(p), p, false, p === currentPage);
				}
				addBtn('›', Math.min(totalPages, currentPage + 1), currentPage >= totalPages, false);
			}

			function applyFilter(resetPage){
				if (resetPage !== false) currentPage = 1;
				var perPage = parseInt((perPageSel && perPageSel.value) || '20', 10);
				if (!perPage || perPage < 1) perPage = 20;
				var matched = getFilteredDataRows();
				var total = matched.length;
				var totalPages = Math.max(1, Math.ceil(total / perPage));
				if (currentPage > totalPages) currentPage = totalPages;
				var startIdx = (currentPage - 1) * perPage;
				var endIdx = startIdx + perPage;
				var visibleByYear = {};
				var allData = table.querySelectorAll('tbody tr.pm-budget-src-data');
				for (var i = 0; i < allData.length; i++) {
					allData[i].style.display = 'none';
				}
				for (var j = 0; j < matched.length; j++) {
					var show = (j >= startIdx && j < endIdx);
					matched[j].style.display = show ? '' : 'none';
					if (show) {
						var y = matched[j].getAttribute('data-budget-year') || '';
						visibleByYear[y] = true;
					}
				}
				var yearRows = table.querySelectorAll('tbody tr.pm-budget-year-row');
				for (var k = 0; k < yearRows.length; k++) {
					var yKey = yearRows[k].getAttribute('data-budget-year') || '';
					yearRows[k].style.display = visibleByYear[yKey] ? '' : 'none';
				}
				if (summary) {
					if (total === 0) {
						summary.textContent = 'ไม่พบรายการ';
					} else {
						var from = startIdx + 1;
						var to = Math.min(endIdx, total);
						summary.textContent = 'แสดง ' + from + '-' + to + ' จาก ' + total + ' รายการ';
					}
				}
				renderPager(totalPages);
			}

			if (inp) inp.addEventListener('input', function(){ applyFilter(true); });
			if (yearSel) yearSel.addEventListener('change', function(){ applyFilter(true); });
			if (perPageSel) perPageSel.addEventListener('change', function(){ applyFilter(true); });
			table.addEventListener('click', function(ev){
				var btn = ev.target.closest('[data-action="toggle-budget-name"]');
				if (!btn) return;
				ev.preventDefault();
				var tr = btn.closest('tr.pm-budget-src-data');
				if (!tr) return;
				var on = tr.classList.toggle('is-expanded');
				btn.textContent = on ? 'ย่อข้อความ' : 'แสดงทั้งหมด';
			});
			applyFilter(true);
		})();
		</script>
	<?php }  //set project budget
	?>

	<?php function set_project_expenses()
	{
		global $db_Project, $db_person;
		$page = $_GET['page'];
		$op_from = $_GET['op'];
		if (function_exists('ensureSetExpensesDetailAttachSchema')) {
			ensureSetExpensesDetailAttachSchema($db_Project);
		}
	?>
		<div class="row mt-4">
			<!-- orders table -->
			<div class="col-12 col-lg-12">
				<div class="bcard card h-100 pt-1 pt-lg-2">

					<div class="card-header border-0">
						<h5 class="card-title text-dark-m2 pl-1 text-110">
							<i class="fas fa-cog text-blue"></i> ประเภทค่าใช้จ่าย
						</h5>
						<style>
							.exp-item-list { margin: 0; padding: 0; list-style: none; }
							.exp-item-card {
								border: 1px solid #e5e7eb;
								border-radius: 8px;
								background: #fafbfc;
								padding: .55rem .7rem .6rem;
								margin-bottom: .55rem;
							}
							.exp-item-card:last-child { margin-bottom: 0; }
							.exp-item-head {
								display: flex;
								align-items: flex-start;
								justify-content: space-between;
								gap: .5rem;
							}
							.exp-item-name {
								font-weight: 700;
								color: #1e293b;
								font-size: .92rem;
								line-height: 1.35;
							}
							.exp-item-name > i {
								font-size: .85rem;
								opacity: .9;
							}
							.exp-item-card.is-off {
								opacity: .62;
								background: #f8fafc;
							}
							.exp-item-actions {
								display: flex;
								align-items: center;
								gap: .35rem;
								flex: 0 0 auto;
								white-space: nowrap;
							}
							.exp-item-view-btn {
								display: inline-flex;
								align-items: center;
								gap: .28rem;
								padding: .18rem .5rem;
								border-radius: 999px;
								border: 1px solid #fecaca;
								background: #fff1f2;
								color: #b91c1c;
								font-size: .72rem;
								font-weight: 700;
								line-height: 1.2;
								text-decoration: none !important;
							}
							.exp-item-view-btn:hover {
								background: #dc2626;
								border-color: #dc2626;
								color: #fff;
							}
							.exp-item-off-badge {
								display: inline-block;
								margin-left: .35rem;
								padding: .08rem .4rem;
								border-radius: 999px;
								background: #e2e8f0;
								color: #475569;
								font-size: .68rem;
								font-weight: 700;
								vertical-align: middle;
							}
							.exp-item-actions .icon-btn i { font-size: .8rem; }
							.exp-item-rule {
								margin-top: .35rem;
								padding-top: .35rem;
								border-top: 1px dashed #dde3ea;
								color: #475569;
								font-size: .82rem;
								line-height: 1.45;
							}
							.exp-item-rule-label {
								display: inline-block;
								font-size: .72rem;
								font-weight: 700;
								color: #64748b;
								margin-bottom: .15rem;
							}
							.exp-item-file {
								margin-top: .4rem;
							}
							.exp-item-file a {
								display: inline-flex;
								align-items: center;
								gap: .3rem;
								color: #1d4ed8;
								font-size: .82rem;
								font-weight: 600;
							}
							.exp-cat-name { font-weight: 700; color: #0f172a; display: block; margin-bottom: .35rem; }
							.exp-pdf-drop {
								position: relative;
								display: flex;
								align-items: center;
								gap: .75rem;
								width: 100%;
								margin: 0;
								padding: .7rem .9rem;
								border: 1.5px dashed #93c5fd;
								border-radius: 10px;
								background: linear-gradient(180deg, #f8fbff 0%, #eef6ff 100%);
								cursor: pointer;
								transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
							}
							.exp-pdf-drop:hover,
							.exp-pdf-drop.is-drag {
								border-color: #2563eb;
								background: #eff6ff;
								box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
							}
							.exp-pdf-drop.has-file {
								border-style: solid;
								border-color: #16a34a;
								background: #f0fdf4;
							}
							.exp-pdf-drop input[type="file"] {
								position: absolute;
								inset: 0;
								opacity: 0;
								cursor: pointer;
								width: 100%;
								height: 100%;
							}
							.exp-pdf-ico {
								flex: 0 0 auto;
								width: 2.4rem;
								height: 2.4rem;
								border-radius: 8px;
								background: #dc2626;
								color: #fff;
								display: inline-flex;
								align-items: center;
								justify-content: center;
								font-size: .78rem;
								font-weight: 800;
								letter-spacing: .02em;
							}
							.exp-pdf-text { min-width: 0; flex: 1 1 auto; }
							.exp-pdf-title {
								display: block;
								font-size: .88rem;
								font-weight: 700;
								color: #1e3a8a;
								line-height: 1.3;
							}
							.exp-pdf-drop.has-file .exp-pdf-title { color: #166534; }
							.exp-pdf-hint {
								display: block;
								font-size: .75rem;
								color: #64748b;
								margin-top: .12rem;
							}
						</style>
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
										<th class="pl-2 pl-lg-4" style="width:4.5rem;">ลำดับที่</th>
										<th style="width:14rem;">หมวดค่าใช้จ่าย</th>
										<th>รายการค่าใช้จ่าย + เกณฑ์/ระเบียบ</th>
										<th style="width:7rem;">สถานะ</th>
										<th style="width:9rem;">จัดการหมวด</th>
									</tr>
								</thead>

								<tbody>
									<?php
									$expenses_data = getSetExpensesList($db_Project, $id);

									if (!empty($expenses_data)) :
										foreach ($expenses_data as $i => $expenses) :
											$expenses_detail_data = getSetExpensesDetailList($db_Project, $expenses['id'], false);
									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4 align-top"><?php echo $i + 1; ?></td>

												<td class="text-dark-m2 text-85 align-top">
													<span class="exp-cat-name"><?php echo htmlspecialchars($expenses['name']); ?></span>
													<a class="btn btn-green btn-xs" href="#modal-add-budget-detail<?= $expenses['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-plus"></i> เพิ่มรายการ
													</a>
												</td>

												<td class="text-dark-m2 text-85 align-top">
													<?php if (!empty($expenses_detail_data)) : ?>
														<ul class="exp-item-list">
														<?php foreach ($expenses_detail_data as $expenses_detail) :
															$detailTxt = trim((string)($expenses_detail['detail'] ?? ''));
															$itemStatus = (string)($expenses_detail['status'] ?? '1');
															$itemOn = ($itemStatus === '1');
															$attachStored = trim((string)($expenses_detail['attach_file'] ?? ''));
															$attachLabel = trim((string)($expenses_detail['attach_name'] ?? ''));
															$attachUrl = ($attachStored !== '' && function_exists('pmExpenseDetailAttachUrl'))
																? pmExpenseDetailAttachUrl($attachStored)
																: '';
															if ($attachLabel === '') {
																$attachLabel = $attachStored;
															}
														?>
															<li class="exp-item-card<?= $itemOn ? '' : ' is-off' ?>">
																<div class="exp-item-head">
																	<div class="exp-item-name">
																		<i class="fas fa-file-invoice-dollar text-blue mr-1" aria-hidden="true"></i>
																		<?= htmlspecialchars($expenses_detail['name']); ?>
																		<?php if (!$itemOn): ?>
																			<span class="exp-item-off-badge">ปิดใช้งาน</span>
																		<?php endif; ?>
																	</div>
																	<div class="exp-item-actions">
																		<?php if ($attachUrl !== ''): ?>
																			<a class="exp-item-view-btn"
																				href="<?= htmlspecialchars($attachUrl, ENT_QUOTES, 'UTF-8') ?>"
																				target="_blank"
																				rel="noopener"
																				title="<?= htmlspecialchars($attachLabel, ENT_QUOTES, 'UTF-8') ?>">
																				<i class="fa fa-file-pdf" aria-hidden="true"></i> คลิกดู
																			</a>
																		<?php endif; ?>
																		<label class="mb-0" title="เปิด/ปิดการใช้งาน">
																			<input type="checkbox" name="status" class="ace-switch"
																				data-id="<?= (int)$expenses_detail['id'] ?>"
																				data-type="set_expenses_detail"
																				<?= $itemOn ? 'checked' : '' ?> />
																		</label>
																		<button type="button"
																			class="btn btn-link p-0 icon-btn text-warning edit"
																			title="แก้ไขรายการ"
																			data-target="#modal-edit-expenses-detail-project<?= $expenses_detail['id']; ?>"
																			data-toggle="modal">
																			<i class="fa fa-edit"></i>
																		</button>
																		<button type="button"
																			class="btn btn-link p-0 icon-btn text-danger exp-item-del-btn"
																			title="ลบรายการ"
																			data-id="<?= (int)$expenses_detail['id'] ?>"
																			data-name="<?= htmlspecialchars((string)$expenses_detail['name'], ENT_QUOTES, 'UTF-8') ?>">
																			<i class="fa fa-trash"></i>
																		</button>
																	</div>
																</div>
																<div class="exp-item-rule">
																	<span class="exp-item-rule-label">เกณฑ์/ระเบียบ</span>
																	<div><?= $detailTxt !== '' ? nl2br(htmlspecialchars($detailTxt)) : '<span class="text-muted">-</span>'; ?></div>
																</div>
															<!-- แก้ไข รายละเอียด -->
															<div class="modal fade modal-lg" id="modal-edit-expenses-detail-project<?= $expenses_detail['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

																<div class="modal-dialog " role="document">
																	<div class="modal-content">

																		<div class="modal-header">
																			<h5 class="modal-title text-blue" id="exampleModalLabel2">
																				<i class="fas fa-cog text-secondary"></i> แก้ไขรายการค่าใช้จ่าย หมวด <?= htmlspecialchars($expenses['name']); ?>
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
																								รายการค่าใช้จ่าย
																							</div>
																							<div class=col-md-8>
																								<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= htmlspecialchars($expenses_detail['name']); ?>" required />

																							</div>
																						</div>
																						<div class="row col-md-12 mt-3">
																							<div class=col-md-4>
																								<i class="fas fa-layer-group text-blue"></i>
																								คำอธิบายเกณฑ์/ระเบียบการเบิกจ่าย
																							</div>
																							<div class=col-md-8>
																								<textarea class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="detail"><?= htmlspecialchars($expenses_detail['detail'] ?? ''); ?></textarea>



																							</div>
																						</div>
																						<div class="row col-md-12 mt-3">
																							<div class="col-md-4">
																								<i class="fas fa-paperclip text-blue"></i>
																								เอกสารแนบ
																							</div>
																							<div class="col-md-8">
																								<?php
																									$editAttachStored = trim((string)($expenses_detail['attach_file'] ?? ''));
																									$editAttachLabel = trim((string)($expenses_detail['attach_name'] ?? ''));
																									$editAttachUrl = ($editAttachStored !== '' && function_exists('pmExpenseDetailAttachUrl'))
																										? pmExpenseDetailAttachUrl($editAttachStored)
																										: '';
																									if ($editAttachLabel === '') {
																										$editAttachLabel = $editAttachStored;
																									}
																								?>
																								<?php if ($editAttachUrl !== ''): ?>
																									<div class="mb-2">
																										<a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($editAttachUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
																											<i class="fa fa-file-pdf text-danger mr-1"></i>
																											<?= htmlspecialchars($editAttachLabel, ENT_QUOTES, 'UTF-8') ?>
																										</a>
																									</div>
																									<label class="mb-2 d-block text-80">
																										<input type="checkbox" name="remove_attach" value="1">
																										ลบเอกสารแนบเดิม
																									</label>
																								<?php endif; ?>
																								<label class="exp-pdf-drop">
																									<input type="file" name="attach_file" accept="application/pdf,.pdf">
																									<span class="exp-pdf-ico">PDF</span>
																									<span class="exp-pdf-text">
																										<span class="exp-pdf-title">คลิกเพื่อเลือกไฟล์ PDF</span>
																										<span class="exp-pdf-hint">เฉพาะไฟล์ PDF ไม่เกิน 15 MB<?= $editAttachUrl !== '' ? ' — เลือกไฟล์ใหม่จะแทนที่ไฟล์เดิม' : '' ?></span>
																									</span>
																								</label>
																							</div>
																						</div>

																					</div>
																					<input type="hidden" name="op_from" value="expenses_detail">
																					<input type="hidden" name="id" value="<?= $expenses['id']; ?>">
																					<input type="hidden" name="expenses_id" value="<?= $expenses_detail['id']; ?>">

																					<div class="offset-md-4 col-md-8 text-nowrap mt-3">
																						<button class="btn btn-warning btn-bold px-4" type="submit">
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
															<!-- แก้ไข รายละเอียด -->
															</li>
														<?php endforeach; ?>
														</ul>
													<?php else : ?>
														<span class="text-muted">ยังไม่มีรายการ — กด “เพิ่มรายการ”</span>
													<?php endif; ?>
												</td>
												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3 align-top">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $expenses['id'] ?>" data-type="set_expenses"
															<?php if ($expenses['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95 align-top">
													<a class="btn btn-warning text-600 btn-xs mr-1 mb-1" href="#modal-edit-set-report_round<?= $expenses['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1 mb-1" href="#modal-del-set-report_round<?= $expenses['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-report_round<?= $expenses['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $expenses['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<input type="hidden" name="tb_data" value="set_project_expenses">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-report_round<?= $expenses['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> ประเภทค่าใช้จ่าย
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
																				ประเภทค่าใช้จ่าย
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $expenses['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $expenses['id']; ?>">

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
											<div class="modal fade modal-lg" id="modal-add-budget-detail<?= $expenses['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> เพิ่มรายการค่าใช้จ่าย หมวด <?= $expenses['name']; ?>
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


																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				รายการค่าใช้จ่าย
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required />

																			</div>
																		</div>
																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				คำอธิบายเกณฑ์/ระเบียบการเบิกจ่าย
																			</div>
																			<div class=col-md-8>
																				<textarea class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="detail" required /></textarea>



																			</div>
																		</div>
																		<div class="row col-md-12 mt-3">
																			<div class="col-md-4">
																				<i class="fas fa-paperclip text-blue"></i>
																				เอกสารแนบ
																			</div>
																			<div class="col-md-8">
																				<label class="exp-pdf-drop">
																					<input type="file" name="attach_file" accept="application/pdf,.pdf">
																					<span class="exp-pdf-ico">PDF</span>
																					<span class="exp-pdf-text">
																						<span class="exp-pdf-title">คลิกเพื่อเลือกไฟล์ PDF</span>
																						<span class="exp-pdf-hint">เฉพาะไฟล์ PDF ไม่เกิน 15 MB</span>
																					</span>
																				</label>
																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="expenses_detail">
																	<input type="hidden" name="id" value="<?= $expenses['id']; ?>">

																	<div class="offset-md-4 col-md-8 text-nowrap mt-3">
																		<button class="btn btn-warning btn-bold px-4" type="submit">
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
											<!-- จบ รายละเอียด -->

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
												<i class="fas fa-cog text-secondary"></i> ประเภทค่าใช้จ่าย
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
																		ประเภทค่าใช้จ่าย
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
		<script>
		(function(){
			function bindExpPdfDrop(root){
				if (!root || root.getAttribute('data-exp-pdf-bound') === '1') return;
				root.setAttribute('data-exp-pdf-bound', '1');
				var input = root.querySelector('input[type="file"]');
				var title = root.querySelector('.exp-pdf-title');
				var hint = root.querySelector('.exp-pdf-hint');
				if (!input || !title) return;
				var defaultTitle = title.textContent;
				var defaultHint = hint ? hint.textContent : '';
				function showName(){
					var f = input.files && input.files[0] ? input.files[0] : null;
					if (!f) {
						root.classList.remove('has-file');
						title.textContent = defaultTitle;
						if (hint) hint.textContent = defaultHint;
						return;
					}
					var ok = /\.pdf$/i.test(f.name) && (f.type === '' || f.type === 'application/pdf' || f.type === 'application/x-pdf');
					if (!ok) {
						input.value = '';
						root.classList.remove('has-file');
						title.textContent = defaultTitle;
						if (hint) hint.textContent = 'กรุณาเลือกเฉพาะไฟล์ PDF';
						return;
					}
					root.classList.add('has-file');
					title.textContent = f.name;
					if (hint) {
						var mb = (f.size / (1024 * 1024));
						hint.textContent = 'พร้อมอัปโหลด · ' + (mb >= 0.1 ? mb.toFixed(2) + ' MB' : Math.max(1, Math.round(f.size / 1024)) + ' KB');
					}
				}
				input.addEventListener('change', showName);
				root.addEventListener('dragover', function(e){ e.preventDefault(); root.classList.add('is-drag'); });
				root.addEventListener('dragleave', function(){ root.classList.remove('is-drag'); });
				root.addEventListener('drop', function(){ root.classList.remove('is-drag'); });
			}
			function bindAll(){
				document.querySelectorAll('.exp-pdf-drop').forEach(bindExpPdfDrop);
			}
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', bindAll);
			} else {
				bindAll();
			}
			if (window.jQuery) {
				jQuery(document).on('shown.bs.modal', bindAll);
			}
			document.addEventListener('click', function(ev){
				var btn = ev.target.closest ? ev.target.closest('.exp-item-del-btn') : null;
				if (!btn) return;
				ev.preventDefault();
				ev.stopPropagation();
				var id = String(btn.getAttribute('data-id') || '');
				var name = String(btn.getAttribute('data-name') || '');
				if (!id) return;
				var runDel = function(){
					var f = document.getElementById('expDetailDelForm');
					if (!f) return;
					f.action = '?page=<?= htmlspecialchars((string)$page, ENT_QUOTES, 'UTF-8') ?>&op=del_set_all&id=' + encodeURIComponent(id);
					var hid = document.getElementById('expDetailDelId');
					if (hid) hid.value = id;
					f.submit();
				};
				var msg = name ? ('ยืนยันลบรายการ “' + name + '” ?') : 'ยืนยันลบรายการนี้?';
				if (window.Swal && typeof Swal.fire === 'function') {
					Swal.fire({
						icon: 'warning',
						title: 'ยืนยันการลบ',
						text: msg,
						showCancelButton: true,
						confirmButtonText: 'ลบ',
						cancelButtonText: 'ยกเลิก',
						confirmButtonColor: '#dc2626'
					}).then(function(r){ if (r.isConfirmed) runDel(); });
				} else if (window.confirm(msg)) {
					runDel();
				}
			}, true);
			document.addEventListener('change', function(ev){
				var el = ev.target;
				if (!el || !el.classList || !el.classList.contains('ace-switch')) return;
				if (el.getAttribute('data-type') !== 'set_expenses_detail') return;
				var card = el.closest('.exp-item-card');
				if (!card) return;
				if (el.checked) card.classList.remove('is-off');
				else card.classList.add('is-off');
			});
		})();
		</script>
		<form id="expDetailDelForm" method="POST" action="?page=<?= htmlspecialchars((string)$page, ENT_QUOTES, 'UTF-8') ?>&op=del_set_all" style="display:none">
			<input type="hidden" name="op_from" value="set_project_expenses">
			<input type="hidden" name="tb_data" value="set_project_expenses_detail">
			<input type="hidden" name="id" id="expDetailDelId" value="">
		</form>
	<?php }  //set report_round
	?>
	<?php function set_project_status()
	{
		global $db_Project, $db_person;
		$page = $_GET['page'];
		$op_from = $_GET['op'];
	?>
		<div class="row mt-4">
			<!-- orders table -->
			<div class="col-12 col-lg-12">
				<div class="bcard card h-100 pt-1 pt-lg-2">

					<div class="card-header border-0">
						<h5 class="card-title text-dark-m2 pl-1 text-110">
							<i class="fas fa-cog text-blue"></i> สถานะโครงการ
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
											สถานะโครงการ
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
									$status_data = getSetStatusList($db_Project, $id);

									if (!empty($status_data)) :
										foreach ($status_data as $i => $status) :

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($status['name']); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $status['id'] ?>" data-type="set_status"
															<?php if ($status['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-type<?= $status['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-type<?= $status['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-type<?= $status['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $status['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<input type="hidden" name="tb_data" value="set_project_status">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-type<?= $status['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> สถานะโครงการ
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
																				สถานะโครงการ
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $status['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $status['id']; ?>">

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
												<i class="fas fa-cog text-secondary"></i> สถานะโครงการ
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
																		สถานะโครงการ
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
	<?php function set_project_target()
	{
		global $db_Project, $db_person;
		$page = $_GET['page'];
		$op_from = $_GET['op'];
	?>
		<div class="row mt-4">
			<!-- orders table -->
			<div class="col-12 col-lg-12">
				<div class="bcard card h-100 pt-1 pt-lg-2">

					<div class="card-header border-0">
						<h5 class="card-title text-dark-m2 pl-1 text-110">
							<i class="fas fa-cog text-blue"></i> กลุ่มเป้าหมาย
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
											กลุ่มเป้าหมาย
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
									$target_data = getSetTargetList($db_Project, $id);

									if (!empty($target_data)) :
										foreach ($target_data as $i => $target) :

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($target['name']); ?>
												</td>



												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $target['id'] ?>" data-type="set_target"
															<?php if ($target['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-so<?= $target['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-so<?= $target['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-so<?= $target['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $target['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<input type="hidden" name="tb_data" value="set_project_target">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-so<?= $target['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> กลุ่มเป้าหมาย
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
																				กลุ่มเป้าหมาย
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $target['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $target['id']; ?>">

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
												<i class="fas fa-cog text-secondary"></i> กลุ่มเป้าหมาย
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
																		กลุ่มเป้าหมาย
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

	<?php function set_project_personnel()
	{
		global $db_Project, $db_person, $op;
		$page = $_GET['page'];
		$op_from = ($op !== '') ? $op : 'set_project_personnel';
		$department_rows = getSetDepartmentListForPlan($db_Project);
		$unit_rows = getSetUnitListForPlan($db_Project);
		$job_rows = getSetJobListForPlan($db_Project);
		$personnel_all = getSetPersonnelList($db_Project, '');
		if (!function_exists('staffMainUnitDisplayName')) {
			include_once(__DIR__ . '/../fn/main_staff.php');
		}
		$staffDisplayById = [];
		$staffIdsNeed = [];
		foreach ($personnel_all as $_p) {
			$_sid = (int) ($_p['staff_id'] ?? 0);
			if ($_sid > 0) {
				$staffIdsNeed[$_sid] = true;
			}
		}
		if (!empty($staffIdsNeed) && $db_person) {
			$idsList = array_keys($staffIdsNeed);
			$idsList = array_values(array_filter(array_map('intval', $idsList), function ($v) {
				return $v > 0;
			}));
			if (!empty($idsList)) {
				$inSql = implode(',', $idsList);
				$qsStaff = mysqli_query($db_person, "SELECT id, unit_code, job_code, dep_code, position_code FROM staff WHERE id IN ($inSql)");
				if ($qsStaff) {
					while ($sr = mysqli_fetch_assoc($qsStaff)) {
						$sid = (int) ($sr['id'] ?? 0);
						if ($sid <= 0) {
							continue;
						}
						$mainU = staffMainUnitDisplayName($db_person, $sr);
						$posN = '';
						$pc = trim((string) ($sr['position_code'] ?? ''));
						if ($pc !== '' && function_exists('getPosition')) {
							$rp = getPosition($db_person, $pc);
							if (is_array($rp)) {
								$posN = trim((string) ($rp['name'] ?? ''));
							}
						}
						$staffDisplayById[$sid] = [
							'main_unit' => $mainU,
							'position' => $posN,
						];
					}
				}
			}
		}
		$staffDisplayByName = [];
		if ($db_person && function_exists('planBuildStaffDisplayByNameMap')) {
			$staffDisplayByName = planBuildStaffDisplayByNameMap($db_person, $personnel_all);
		}
		$unit_map = [];
		foreach ($unit_rows as $uu) {
			$uid = (int)($uu['id'] ?? 0);
			if ($uid > 0) {
				$unit_map[$uid] = $uu;
			}
		}

	// Preload รายชื่อ staff ชุดแรก เพื่อให้เลือกได้ทันที (แม้ Select2/AJAX ยังไม่ตอบกลับ)
	$staff_initial = [];
	try {
		$res = mysqli_query(
			$db_person,
			"SELECT code, CONCAT(TRIM(fname),' ',TRIM(lname)) AS full_name
			 FROM staff
			 WHERE work_status IN ('0','1')
			 ORDER BY fname ASC, lname ASC, code ASC
			 LIMIT 20"
		);
		if ($res) {
			while ($row = mysqli_fetch_assoc($res)) {
				$code = (string)($row['code'] ?? '');
				$full = trim((string)($row['full_name'] ?? ''));
				if ($code === '' || $full === '') continue;
				$staff_initial[] = [
					'code' => $code,
					'text' => $full,
				];
			}
		}
	} catch (Throwable $e) {
		@error_log('set_project_personnel staff_initial: ' . $e->getMessage());
	}

		// โครงสร้างที่แสดงบนหน้า: เฉพาะหน่วยงานที่เพิ่มเอง (ไม่ผูก job = เดิมกลุ่มที่ 2)
		// set_department / set_job ยังโหลดไว้สำหรับ modal เพิ่มหน่วยงาน (สังกัดโครงสร้างหลัก แบบไม่บังคับ)
		$departmentByCode = [];
		$departmentById = [];
		foreach ($department_rows as $d) {
			$did = (int)($d['id'] ?? 0);
			if ($did > 0) {
				$departmentById[$did] = $d;
			}
			$dc = trim((string)($d['code'] ?? ''));
			if ($dc !== '') {
				$departmentByCode[$dc] = $d;
			}
		}
		$jobByCode = [];
		$jobById = [];
		foreach ($job_rows as $j) {
			$jid = (int)($j['id'] ?? 0);
			if ($jid > 0) {
				$jobById[$jid] = $j;
			}
			$jc = trim((string)($j['code'] ?? ''));
			if ($jc !== '') {
				$jobByCode[$jc] = $j;
			}
		}
		$unitByCode = [];
		foreach ($unit_rows as $u) {
			$uc = trim((string)($u['code'] ?? ''));
			if ($uc !== '') {
				$unitByCode[$uc] = $u;
			}
		}

		$personnelByStaffUnit = [];
		$personnelRowsByUnitId = [];
		foreach ($personnel_all as $pr) {
			$sid = (int)($pr['staff_id'] ?? 0);
			$cuid = (int)($pr['code_unit'] ?? 0);
			if ($cuid > 0) {
				if (!isset($personnelRowsByUnitId[$cuid])) {
					$personnelRowsByUnitId[$cuid] = [];
				}
				$personnelRowsByUnitId[$cuid][] = $pr;
			}
			if ($sid <= 0) {
				continue;
			}
			if ($cuid > 0) {
				$personnelByStaffUnit[$sid][$cuid] = $pr;
			}
		}

		$staffFromPerson = [];
		try {
			$qStaff = mysqli_query(
				$db_person,
				"SELECT id, code, fname, lname, dep_code, job_code, unit_code, position_code
				 FROM staff
				 WHERE work_status IN ('0','1')
				 ORDER BY dep_code ASC, job_code ASC, unit_code ASC, fname ASC, lname ASC, code ASC"
			);
			if ($qStaff) {
				while ($sr = mysqli_fetch_assoc($qStaff)) {
					$staffFromPerson[] = $sr;
				}
			}
		} catch (Throwable $e) {
			@error_log('set_project_personnel person source: ' . $e->getMessage());
		}

		$staffByUnitCode = [];
		$staffByDepCode = [];
		$staffByDepJobNoUnit = [];
		$staffByMainUnitName = [];
		foreach ($staffFromPerson as $sr) {
			$dc = trim((string)($sr['dep_code'] ?? ''));
			$jc = trim((string)($sr['job_code'] ?? ''));
			$uc = trim((string)($sr['unit_code'] ?? ''));
			$mainUnitName = staffMainUnitDisplayName($db_person, $sr);
			if ($mainUnitName !== '') {
				if (!isset($staffByMainUnitName[$mainUnitName])) {
					$staffByMainUnitName[$mainUnitName] = [];
				}
				$staffByMainUnitName[$mainUnitName][] = $sr;
			}
			if ($dc !== '') {
				if (!isset($staffByDepCode[$dc])) {
					$staffByDepCode[$dc] = [];
				}
				$staffByDepCode[$dc][] = $sr;
			}
			if ($dc !== '' && $jc !== '' && $uc === '') {
				$keyDj = $dc . '|' . $jc;
				if (!isset($staffByDepJobNoUnit[$keyDj])) {
					$staffByDepJobNoUnit[$keyDj] = [];
				}
				$staffByDepJobNoUnit[$keyDj][] = $sr;
			}
			if ($uc === '') {
				continue;
			}
			if (!isset($staffByUnitCode[$uc])) {
				$staffByUnitCode[$uc] = [];
			}
			$staffByUnitCode[$uc][] = $sr;
		}

		$buildRowsForUnit = function (int $secUid, string $unitCode) use ($staffByUnitCode, $personnelByStaffUnit, $db_person) {
			$rows = [];
			if ($unitCode === '' || !isset($staffByUnitCode[$unitCode])) {
				return $rows;
			}
			foreach ($staffByUnitCode[$unitCode] as $sr) {
				$sid = (int)($sr['id'] ?? 0);
				$staffName = trim((string)($sr['fname'] ?? '') . ' ' . (string)($sr['lname'] ?? ''));
				if ($sid <= 0 || $staffName === '') {
					continue;
				}
				// ใช้เฉพาะแถวที่ผูก code_unit กับหน่วยนี้จริง — ห้าม fallback ไป id ของหน่วยอื่น (จะทำให้ปุ่ม Admin/ลบผิดหน่วย)
				$linked = isset($personnelByStaffUnit[$sid][$secUid]) ? $personnelByStaffUnit[$sid][$secUid] : null;
				$linkedId = (int)($linked['id'] ?? 0);
				$isUa = (int)($linked['is_unit_admin'] ?? 0);
				$mainUnit = staffMainUnitDisplayName($db_person, $sr);
				$posName = '';
				$pc = trim((string)($sr['position_code'] ?? ''));
				if ($pc !== '' && function_exists('getPosition')) {
					$rp = getPosition($db_person, $pc);
					if (is_array($rp)) {
						$posName = trim((string)($rp['name'] ?? ''));
					}
				}
				$rows[] = [
					'id' => $linkedId,
					'staff_id' => $sid,
					'name' => $staffName,
					'department_name' => $mainUnit,
					'job_name' => $posName,
					'is_unit_admin' => $isUa,
				];
			}

			return $rows;
		};
		$buildRowsForGroup2 = function (int $secUid) use ($personnelRowsByUnitId) {
			if ($secUid <= 0 || !isset($personnelRowsByUnitId[$secUid])) {
				return [];
			}

			return $personnelRowsByUnitId[$secUid];
		};
		$buildRowsForDepartment = function (string $depCode) use ($staffByDepCode, $db_person) {
			$rows = [];
			if ($depCode === '' || !isset($staffByDepCode[$depCode])) {
				return $rows;
			}
			foreach ($staffByDepCode[$depCode] as $sr) {
				$sid = (int)($sr['id'] ?? 0);
				$staffName = trim((string)($sr['fname'] ?? '') . ' ' . (string)($sr['lname'] ?? ''));
				if ($sid <= 0 || $staffName === '') {
					continue;
				}
				$mainUnit = staffMainUnitDisplayName($db_person, $sr);
				$posName = '';
				$pc = trim((string)($sr['position_code'] ?? ''));
				if ($pc !== '' && function_exists('getPosition')) {
					$rp = getPosition($db_person, $pc);
					if (is_array($rp)) {
						$posName = trim((string)($rp['name'] ?? ''));
					}
				}
				$rows[] = [
					'id' => 0,
					'staff_id' => $sid,
					'name' => $staffName,
					'department_name' => $mainUnit,
					'job_name' => $posName,
					'is_unit_admin' => 0,
				];
			}

			return $rows;
		};
		$buildRowsForDepJobNoUnit = function (string $depCode, string $jobCode) use ($staffByDepJobNoUnit, $db_person) {
			$rows = [];
			$key = $depCode . '|' . $jobCode;
			if ($depCode === '' || $jobCode === '' || !isset($staffByDepJobNoUnit[$key])) {
				return $rows;
			}
			foreach ($staffByDepJobNoUnit[$key] as $sr) {
				$sid = (int)($sr['id'] ?? 0);
				$staffName = trim((string)($sr['fname'] ?? '') . ' ' . (string)($sr['lname'] ?? ''));
				if ($sid <= 0 || $staffName === '') {
					continue;
				}
				$mainUnit = staffMainUnitDisplayName($db_person, $sr);
				$posName = '';
				$pc = trim((string)($sr['position_code'] ?? ''));
				if ($pc !== '' && function_exists('getPosition')) {
					$rp = getPosition($db_person, $pc);
					if (is_array($rp)) {
						$posName = trim((string)($rp['name'] ?? ''));
					}
				}
				$rows[] = [
					'id' => 0,
					'staff_id' => $sid,
					'name' => $staffName,
					'department_name' => $mainUnit,
					'job_name' => $posName,
					'is_unit_admin' => 0,
				];
			}

			return $rows;
		};

		$unitByJobKey = [];
		$group2Units = [];
		foreach ($unit_rows as $uu) {
			$secUid = (int)($uu['id'] ?? 0);
			if ($secUid <= 0) {
				continue;
			}
			$jobCodeText = trim((string)($uu['job_code'] ?? ''));
			$codeJobNum = (int)($uu['code_job'] ?? 0);
			if ($jobCodeText === '' && $codeJobNum <= 0) {
				$group2Units[] = $uu;
				continue;
			}
			$jobKeys = [];
			if ($codeJobNum > 0) {
				$jobKeys[] = 'id:' . $codeJobNum;
			}
			if ($jobCodeText !== '') {
				$jobKeys[] = 'code:' . $jobCodeText;
			}
			foreach ($jobKeys as $jobKey) {
				if (!isset($unitByJobKey[$jobKey])) {
					$unitByJobKey[$jobKey] = [];
				}
				$unitByJobKey[$jobKey][$secUid] = $uu; // กันซ้ำในกรณีมีทั้ง id/code
			}
		}
		foreach ($unitByJobKey as &$listRef) {
			$listRef = array_values($listRef);
			usort($listRef, function ($a, $b) {
				$an = trim((string)($a['name'] ?? ''));
				$bn = trim((string)($b['name'] ?? ''));
				return strcmp($an, $bn);
			});
		}
		unset($listRef);
		usort($group2Units, function ($a, $b) {
			$an = trim((string)($a['name'] ?? ''));
			$bn = trim((string)($b['name'] ?? ''));
			return strcmp($an, $bn);
		});

		$jobsByDepCode = [];
		foreach ($job_rows as $jr) {
			$depCodeRef = trim((string)($jr['dep_code'] ?? ''));
			if (!isset($jobsByDepCode[$depCodeRef])) {
				$jobsByDepCode[$depCodeRef] = [];
			}
			$jobsByDepCode[$depCodeRef][] = $jr;
		}
		foreach ($jobsByDepCode as &$jobListRef) {
			usort($jobListRef, function ($a, $b) {
				$ac = trim((string)($a['code'] ?? ''));
				$bc = trim((string)($b['code'] ?? ''));
				if ($ac !== $bc) {
					return strcmp($ac, $bc);
				}

				return strcmp(trim((string)($a['name'] ?? '')), trim((string)($b['name'] ?? '')));
			});
		}
		unset($jobListRef);

		// แสดงเฉพาะกลุ่มที่ 2 (หน่วยงานที่เพิ่มเอง ไม่ผูก job) — กลุ่มที่ 1 ไม่สร้าง section แล้ว
		// ข้อมูล set_department / set_job / set_unit กลุ่มที่ 1 ยังอยู่ในฐานเพื่อโครงการเก่า / สิทธิ์ / memo
		$sections = [];

		foreach ($group2Units as $uu) {
			$secUid = (int)($uu['id'] ?? 0);
			$secTitle = function_exists('planSetUnitOptionLabel')
				? planSetUnitOptionLabel($uu)
				: trim((string)($uu['name'] ?? ''));
			if ($secTitle === '') {
				$secTitle = trim((string)($uu['name'] ?? ''));
			}
			$unitCode = trim((string)($uu['code'] ?? ''));
			$sections[] = [
				'uid' => $secUid,
				'unit_code' => $unitCode,
				'title' => $secTitle !== '' ? $secTitle : ('หน่วยงาน #' . $secUid),
				'rows' => $buildRowsForGroup2($secUid),
				'group_label' => 'หน่วยงาน',
				'sort_dep' => '',
				'sort_job' => '',
				'sort_unit' => trim((string)($uu['name'] ?? '')),
				'dep_label' => '',
				'job_label' => '',
				'manage_kind' => 'unit',
				'manage_id' => $secUid,
				'manage_name' => trim((string)($uu['name'] ?? '')),
				'manage_paper_name' => trim((string)($uu['paper_name'] ?? '')),
				'manage_paper_start' => trim((string)($uu['paper_start'] ?? '')),
				'manage_code_job' => (int)($uu['code_job'] ?? 0),
				'manage_code_department' => (int)($uu['code_department'] ?? 0),
				'manage_status' => (int)($uu['status'] ?? 1),
				'dep_paper_name' => '',
				'dep_paper_start' => '',
			];
		}

		// รายชื่อ Admin ของหน่วยงาน (is_unit_admin=1) — ใช้แสดงใต้หัวข้อหน่วยงาน
		$sectionUnitAdminNames = [];
		foreach ($sections as $_sec) {
			$_uid = (int) ($_sec['uid'] ?? 0);
			$list = [];
			if (!empty($_sec['rows'])) {
				foreach ($_sec['rows'] as $_pr) {
					if (!isset($_pr['is_unit_admin']) || (int) $_pr['is_unit_admin'] !== 1) {
						continue;
					}
					$_nm = trim((string) ($_pr['name'] ?? ''));
					if ($_nm !== '') {
						$list[] = $_nm;
					}
				}
				$list = array_values(array_unique($list));
			}
			$sectionUnitAdminNames[$_uid] = $list;
		}
	?>
		<style>
			.unit-paper-badge {
				display: inline-flex !important;
				align-items: center;
				padding: 0.22rem 0.72rem !important;
				border-radius: 999px !important;
				background: linear-gradient(135deg, #926cb8 0%, #734d96 100%) !important;
				color: #fff !important;
				font-size: 0.78rem !important;
				font-weight: 700 !important;
				line-height: 1.1;
				letter-spacing: 0.2px;
				border: 1px solid rgba(255, 255, 255, 0.35);
				box-shadow: 0 2px 8px rgba(146, 108, 184, 0.35);
				vertical-align: middle;
			}
			.unit-title-hit {
				cursor: pointer;
				user-select: none;
			}
			.unit-title-hit:hover .unit-title-text {
				text-decoration: underline;
			}
			.plan-unit-group.is-expanded .unit-toggle-chevron {
				transform: rotate(180deg);
			}
			.unit-toggle-chevron {
				display: inline-block;
				transition: transform 0.2s ease;
				vertical-align: middle;
				font-size: 0.75rem;
				opacity: 0.85;
			}
			.plan-indent-group {
				padding-left: 0.75rem !important;
			}
			.plan-indent-dep {
				padding-left: 2rem !important;
			}
			.plan-indent-job {
				padding-left: 3.5rem !important;
			}
			.plan-indent-unit {
				padding-left: 5rem !important;
			}
			.plan-indent-personnel-name {
				padding-left: 1.25rem !important;
			}
			.plan-group-row td {
				background: linear-gradient(90deg, #f3eef8 0%, #faf7fc 100%) !important;
				border-top: 2px solid #e8def2 !important;
				border-bottom: 2px solid #e8def2 !important;
				font-size: 1rem !important;
				font-weight: 800 !important;
				color: #4a3263 !important;
				letter-spacing: 0.2px;
				text-transform: none;
			}
			.plan-group-row .group-label-chip {
				display: inline-flex;
				align-items: center;
				padding: 0.2rem 0.65rem;
				margin-right: 0.5rem;
				border-radius: 999px;
				background: #0b6cbf;
				color: #fff;
				font-size: 0.8rem;
				font-weight: 800;
			}
		</style>
		<div class="row mt-4">
			<!-- orders table -->
			<div class="col-12 col-lg-12">
				<div class="bcard card h-100 pt-1 pt-lg-2">

					<div class="card-header border-0 d-flex flex-wrap align-items-center">
						<h5 class="card-title text-dark-m2 pl-1 text-110 mb-0 flex-grow-1">
							<i class="fas fa-cog text-blue"></i> หน่วยงาน
						</h5>
						<div class="card-toolbar align-self-center no-border mr-2 my-1">
							<input type="search" id="plan-unit-table-search" class="form-control form-control-sm"
								placeholder="ค้นหาหน่วยงาน…" autocomplete="off"
								style="min-width:200px;max-width:280px;" />
						</div>
						<div class="card-toolbar align-self-center no-border">
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-personnel" role="button" data-toggle="modal">
											<i class="fas fa-plus"></i> เพิ่มหน่วยงาน
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
											รายชื่อ
										</th>
										<th>
											สังกัด <span class="text-75 text-muted font-normal">(หลัก)</span>
										</th>
										<th>
											ตำแหน่ง / งาน
										</th>
										<th class="text-center text-nowrap">
											Admin หน่วยงาน
										</th>
										<th>
											สถานะการใช้งาน
										</th>

										<th>
											จัดการข้อมูล
										</th>

									</tr>
								</thead>

									<?php
									if (!empty($sections)) :
										$lastGroupLabel = '';
										$lastDepLabel = '';
										$lastJobLabel = '';
										foreach ($sections as $sec) :
											$seq = 0;
											$secUid = (int) ($sec['uid'] ?? 0);
											$secGroupLabel = trim((string) ($sec['group_label'] ?? ''));
											$secDepLabel = trim((string) ($sec['dep_label'] ?? ''));
											$secJobLabel = trim((string) ($sec['job_label'] ?? ''));
											$secDepPaperName = trim((string) ($sec['dep_paper_name'] ?? ''));
											$secDepPaperStart = trim((string) ($sec['dep_paper_start'] ?? ''));
											$secTitleRaw = (string) ($sec['title'] ?? '');
											$secSearchExtra = '';
											if (!empty($sectionUnitAdminNames[$secUid])) {
												$secSearchExtra = ' ' . implode(' ', $sectionUnitAdminNames[$secUid]);
											}
											$secSearchBlob = $secTitleRaw . $secSearchExtra;
											$secSearchKey = function_exists('mb_strtolower')
												? mb_strtolower($secSearchBlob, 'UTF-8')
												: strtolower($secSearchBlob);
											$isGroup1Section = (strpos($secGroupLabel, 'กลุ่มที่ 1') === 0);
									?>
								<tbody class="plan-unit-group"
									data-unit-parent-id="<?= $secUid; ?>"
									data-unit-search-text="<?= htmlspecialchars($secSearchKey, ENT_QUOTES, 'UTF-8'); ?>">
											<?php if ($secGroupLabel !== '' && $secGroupLabel !== $lastGroupLabel) : ?>
											<tr class="bgc-secondary-l4 plan-group-row">
												<td colspan="7" class="py-2 font-bolder text-primary-d2 plan-indent-group">
													<span class="group-label-chip"><i class="fas fa-layer-group mr-1"></i> กลุ่ม</span>
													<?= htmlspecialchars($secGroupLabel, ENT_QUOTES, 'UTF-8'); ?>
												</td>
											</tr>
											<?php $lastGroupLabel = $secGroupLabel; $lastDepLabel = ''; $lastJobLabel = ''; endif; ?>
											<?php if (strpos($secGroupLabel, 'กลุ่มที่ 1') === 0 && $secDepLabel !== '' && $secDepLabel !== $lastDepLabel) : ?>
											<tr class="bgc-blue-l5">
												<td colspan="7" class="py-2 font-bolder text-blue-d2 plan-indent-dep">
													<i class="fas fa-building mr-1"></i><?= htmlspecialchars($secDepLabel, ENT_QUOTES, 'UTF-8'); ?>
													<?php
													$depPaperNameShow = trim($secDepPaperName, " \t\n\r\0\x0B/");
													$depPaperStartShow = trim($secDepPaperStart, " \t\n\r\0\x0B/");
													$depPaperBadge = trim($depPaperNameShow . ($depPaperStartShow !== '' ? '/' . $depPaperStartShow : ''), '/');
													?>
													<?php if ($depPaperBadge !== '') : ?>
													<span class="unit-paper-badge ml-2"><?= htmlspecialchars($depPaperBadge, ENT_QUOTES, 'UTF-8'); ?></span>
													<?php endif; ?>
												</td>
											</tr>
											<?php $lastDepLabel = $secDepLabel; $lastJobLabel = ''; endif; ?>
											<?php if (strpos($secGroupLabel, 'กลุ่มที่ 1') === 0 && $secJobLabel !== '' && $secJobLabel !== $lastJobLabel) : ?>
											<tr class="bgc-blue-l4">
												<td colspan="7" class="py-2 font-bolder text-primary-d2 plan-indent-job">
													<i class="fas fa-briefcase mr-1"></i><?= htmlspecialchars($secJobLabel, ENT_QUOTES, 'UTF-8'); ?>
												</td>
											</tr>
											<?php $lastJobLabel = $secJobLabel; endif; ?>
											<tr class="bgc-default-l4 text-90 unit-section-header">
												<td colspan="7" class="py-2 font-bolder text-dark-m2 plan-indent-unit">
													<div class="d-flex align-items-center justify-content-between">
														<div class="unit-title-hit d-flex align-items-center flex-wrap"
															role="button"
															tabindex="0"
															data-unit-toggle="<?= $secUid; ?>"
															aria-expanded="false"
															title="คลิกเพื่อแสดง/ซ่อนรายชื่อในหน่วยงาน">
															<i class="fas fa-chevron-down unit-toggle-chevron text-secondary mr-2" aria-hidden="true"></i>
															<span class="unit-title-text">
															<?php
															$uRowHead = $unit_map[(int)($sec['uid'] ?? 0)] ?? [];
															$uPaperNameHead = trim((string)($uRowHead['paper_name'] ?? ''));
															$uPaperStartHead = trim((string)($uRowHead['paper_start'] ?? ''));
															if ($uPaperNameHead === '') {
																$uPaperNameHead = trim((string)($sec['manage_paper_name'] ?? ''));
															}
															if ($uPaperStartHead === '') {
																$uPaperStartHead = trim((string)($sec['manage_paper_start'] ?? ''));
															}
															$uPaperNameHead = trim($uPaperNameHead, " \t\n\r\0\x0B/");
															$uPaperStartHead = trim($uPaperStartHead, " \t\n\r\0\x0B/");
															$uPaperBadge = trim($uPaperNameHead . ($uPaperStartHead !== '' ? '/' . $uPaperStartHead : ''), '/');
															$uTitleText = (string)($sec['title'] ?? '');
															if (function_exists('planStripUnitCodeFromLabel')) {
																$uTitleText = planStripUnitCodeFromLabel($uTitleText);
															}
															$uTitleParts = explode('—', $uTitleText, 2);
															$uTitleLeft = trim((string)($uTitleParts[0] ?? ''));
															$uTitleRight = trim((string)($uTitleParts[1] ?? ''));
															$uTitleRightDisplay = $uTitleRight;
															// fallback: ถ้าฐานยังไม่มี paper_name/paper_start แต่มีข้อความรูปแบบ xx/yy นำมาแสดงเป็น badge
															if ($uPaperBadge === '' && $uTitleRight !== '') {
																if (preg_match('/^(.+?\/\S+)\s+(.+)$/u', $uTitleRight, $m)) {
																	$uPaperBadge = trim((string)($m[1] ?? ''));
																	$uTitleRightDisplay = trim((string)($m[2] ?? ''));
																}
															}
															?>
															<i class="fas fa-sitemap text-blue mr-1"></i>
															<?php if ($uTitleRight !== '') : ?>
																<?= htmlspecialchars($uTitleLeft, ENT_QUOTES, 'UTF-8'); ?> &mdash;
																<?php if ((int)($sec['uid'] ?? 0) > 0 && $uPaperBadge !== '') : ?>
																	<span class="unit-paper-badge mx-2"><?= htmlspecialchars($uPaperBadge, ENT_QUOTES, 'UTF-8'); ?></span>
																<?php endif; ?>
																<?= htmlspecialchars($uTitleRightDisplay, ENT_QUOTES, 'UTF-8'); ?>
															<?php else : ?>
																<?= htmlspecialchars($uTitleText, ENT_QUOTES, 'UTF-8'); ?>
																<?php if ((int)($sec['uid'] ?? 0) > 0 && $uPaperBadge !== '') : ?>
																	<span class="unit-paper-badge ml-2"><?= htmlspecialchars($uPaperBadge, ENT_QUOTES, 'UTF-8'); ?></span>
																<?php endif; ?>
															<?php endif; ?>
															</span>
														</div>
														<div class="d-flex align-items-center" style="gap:10px;">
															<?php
															$manageKind = trim((string)($sec['manage_kind'] ?? ''));
															$manageId = (int)($sec['manage_id'] ?? 0);
															$canManageUnit = $manageKind === 'unit' && $manageId > 0;
															$canManageDepartment = $manageKind === 'department' && $manageId > 0;
															$canToggleStatus = $canManageUnit || $canManageDepartment;
															$canEditMeta = $canManageUnit || $canManageDepartment;
															$uName = trim((string)($sec['manage_name'] ?? ''));
															$uPaperName = trim((string)($sec['manage_paper_name'] ?? ''));
															$uPaperStart = trim((string)($sec['manage_paper_start'] ?? ''));
															$uCodeJob = (int)($sec['manage_code_job'] ?? 0);
															$uCodeDepartment = (int)($sec['manage_code_department'] ?? 0);
															$uStatus = (int)($sec['manage_status'] ?? 0);
															$statusType = $canManageDepartment ? 'set_department_project' : 'set_unit_project';
															?>
															<label class="mb-0 d-flex align-items-center">
																<input type="checkbox"
																	class="ace-switch"
																	data-id="<?= $manageId; ?>"
																	data-type="<?= $statusType; ?>"
																	<?= $canToggleStatus ? '' : 'disabled'; ?>
																	<?php if ((string)$uStatus === '1') echo 'checked'; ?>
																/>
															</label>
															<button type="button"
																class="btn btn-sm btn-warning px-3 btn-edit-unit"
																data-toggle="modal"
																data-target="#modal-edit-unit"
																data-unit-id="<?= $manageId; ?>"
																data-manage-kind="<?= htmlspecialchars($manageKind, ENT_QUOTES, 'UTF-8'); ?>"
																data-unit-title="<?= htmlspecialchars((string) ($sec['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
																data-unit-name="<?= htmlspecialchars($uName, ENT_QUOTES, 'UTF-8'); ?>"
																data-paper-name="<?= htmlspecialchars($uPaperName, ENT_QUOTES, 'UTF-8'); ?>"
																data-paper-start="<?= htmlspecialchars($uPaperStart, ENT_QUOTES, 'UTF-8'); ?>"
																data-code-job="<?= $uCodeJob; ?>"
																data-code-department="<?= $uCodeDepartment; ?>"
																<?= $canEditMeta ? '' : 'disabled'; ?>>
																<i class="fas fa-edit mr-1"></i> แก้ไขหน่วยงาน
															</button>
															<button type="button"
																class="btn btn-sm btn-danger px-3 btn-del-unit"
																data-toggle="modal"
																data-target="#modal-del-unit"
																data-unit-id="<?= $manageId; ?>"
																data-unit-title="<?= htmlspecialchars((string) ($sec['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
																<?= $canManageUnit ? '' : 'disabled'; ?>>
																<i class="fas fa-trash mr-1"></i> ลบหน่วยงาน
															</button>
															<button type="button"
																class="btn btn-sm btn-info px-3 btn-add-names-to-unit"
																data-toggle="modal"
																data-target="#modal-add-names-to-unit"
																data-unit-id="<?= $manageId; ?>"
																data-unit-title="<?= htmlspecialchars((string) ($sec['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
																<?= $canManageUnit ? '' : 'disabled'; ?>>
																<i class="fas fa-plus mr-1"></i> เพิ่มรายชื่อ
															</button>
														</div>
													</div>
													<?php
														$secAdminNames = $sectionUnitAdminNames[$secUid] ?? [];
														$secAdminHas = !empty($secAdminNames);
													?>
														<div class="w-100 mt-2 pt-2 border-top brc-default-l2 unit-admin-summary<?= $secAdminHas ? '' : ' d-none'; ?>">
															<span class="text-85 text-secondary">
																<i class="fas fa-check-circle text-success mr-1" aria-hidden="true"></i>
																Admin:
															</span>
															<span class="text-85 text-dark-m2 font-bolder unit-admin-summary-names">
																<?= $secAdminHas ? htmlspecialchars(implode(' · ', $secAdminNames), ENT_QUOTES, 'UTF-8') : ''; ?>
															</span>
														</div>
												</td>
											</tr>
									<?php
											if (empty($sec['rows'])) :
									?>
											<tr class="bgc-h-secondary-l4 unit-personnel-subrow d-none">
												<td colspan="7" class="text-center text-90 text-muted py-3">
													ไม่มีรายชื่อในหน่วยงานนี้
												</td>
											</tr>
									<?php
											else :
												foreach ($sec['rows'] as $personnel) :
													$seq++;
													$personnelId = (int) ($personnel['id'] ?? 0);
													$hasPersonnelId = $personnelId > 0;
													$pSid = (int) ($personnel['staff_id'] ?? 0);
													$manageKindSec = trim((string) ($sec['manage_kind'] ?? ''));
													$manageIdSec = (int) ($sec['manage_id'] ?? 0);
													$secUidForUnit = (int) ($sec['uid'] ?? 0);
													// ใช้ uid ของ section (ตรงกับ set_unit.id สำหรับหน่วยจริง) หลีกเลี่ยง manage_id ผิดประเภท
													$setUnitIdForAjax = ($manageKindSec === 'unit' && $secUidForUnit > 0)
														? $secUidForUnit
														: (($manageKindSec === 'unit' && $manageIdSec > 0) ? $manageIdSec : 0);
													$unitCodeAttr = trim((string) ($sec['unit_code'] ?? ''));
													$secManageCd = (int) ($sec['manage_code_department'] ?? 0);
													$secManageCj = (int) ($sec['manage_code_job'] ?? 0);
													$canToggleAdmin = $hasPersonnelId
														|| ($pSid > 0 && $manageKindSec === 'unit' && (
															$setUnitIdForAjax > 0
															|| $unitCodeAttr !== ''
															|| $secManageCd > 0
															|| $secManageCj > 0
														));
													$pNameKey = function_exists('planNormalizePersonDisplayName')
														? planNormalizePersonDisplayName((string) ($personnel['name'] ?? ''))
														: trim((string) ($personnel['name'] ?? ''));
													$affMain = '';
													$posDisp = '';
													if ($pSid > 0 && isset($staffDisplayById[$pSid])) {
														$affMain = $staffDisplayById[$pSid]['main_unit'];
														$posDisp = $staffDisplayById[$pSid]['position'];
													} elseif ($pNameKey !== '' && isset($staffDisplayByName[$pNameKey])) {
														$affMain = $staffDisplayByName[$pNameKey]['main_unit'];
														$posDisp = $staffDisplayByName[$pNameKey]['position'];
													}
													if ($affMain === '') {
														$affMain = trim((string) ($personnel['department_name'] ?? ''));
													}
													if ($posDisp === '') {
														$posDisp = trim((string) ($personnel['job_name'] ?? ''));
													}
													$isUa = isset($personnel['is_unit_admin']) && (int) $personnel['is_unit_admin'] === 1;
													// กลุ่มที่ 1: รายชื่อดึงจาก person — ไม่ให้ลบ (ไม่แสดงกากบาท)
													$canDeletePersonnel = $hasPersonnelId && !$isGroup1Section;
									?>

											<tr class="bgc-h-secondary-l4 unit-personnel-subrow d-none">
												<td class="pl-2 pl-lg-4">
													<?php echo $seq; ?>
												</td>

												<td class=" text-dark-m2 text-85 plan-indent-personnel-name">
													<?php if ($canDeletePersonnel) : ?>
													<a class="text-danger mr-2" href="#modal-del-set-personnel<?= $personnelId; ?>" role="button" data-toggle="modal" title="ลบรายชื่อ">
														<i class="fas fa-times"></i>
													</a>
													<?php endif; ?>
													<?php echo htmlspecialchars($personnel['name']); ?>
												</td>

												<td class="text-dark-m2 text-85">
													<?php echo htmlspecialchars($affMain, ENT_QUOTES, 'UTF-8'); ?>
												</td>

												<td class="text-dark-m2 text-85">
													<?php echo htmlspecialchars($posDisp, ENT_QUOTES, 'UTF-8'); ?>
												</td>

												<td class="text-center text-nowrap align-middle">
													<button type="button"
														class="btn btn-link btn-sm py-0 px-1 btn-unit-admin-toggle rounded-circle"
														title="<?= $isUa ? 'เป็นผู้ดูแลหน่วยงานแล้ว — คลิกเพื่อถอดสิทธิ์' : 'คลิกเพื่อตั้งเป็นผู้ดูแลหน่วยงาน (Admin)'; ?>"
														data-personnel-id="<?= $personnelId; ?>"
														data-staff-id="<?= (int) $pSid; ?>"
														data-person-name="<?= htmlspecialchars(trim((string) ($personnel['name'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
														data-code-unit="<?= (int) $setUnitIdForAjax; ?>"
														data-unit-code="<?= htmlspecialchars($unitCodeAttr, ENT_QUOTES, 'UTF-8'); ?>"
														data-manage-code-department="<?= $secManageCd; ?>"
														data-manage-code-job="<?= $secManageCj; ?>"
														<?= $canToggleAdmin ? '' : 'disabled'; ?>
														aria-pressed="<?= $isUa ? 'true' : 'false'; ?>">
														<i class="fas fa-user-shield fa-lg <?= $isUa ? 'text-warning' : 'text-muted'; ?>" style="<?= $isUa ? '' : 'opacity:0.45'; ?>"></i>
													</button>
												</td>

												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<!-- สถานะย้ายไปควบคุมที่ระดับหน่วยงานแล้ว -->
												</td>

												<td class="text-secondary-d3 text-95"></td>
											</tr>

											<!-- with white backdrop -->
											<?php if ($canDeletePersonnel) : ?>
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-personnel<?= $personnelId; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $personnelId; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<input type="hidden" name="tb_data" value="set_project_personnel">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>
															</form>
														</div>
													</div>
												</div>
											</div>
											<?php endif; ?>

											<!-- แก้ไข -->
											<?php if ($hasPersonnelId) : ?>
											<div class="modal fade modal-lg" id="modal-edit-set-personnel<?= $personnelId; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
												<div class="modal-dialog " role="document">
													<div class="modal-content">
														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> หน่วยงาน
															</h5>

															<button type="button" class="close" data-dismiss="modal" aria-label="Close">
																<span aria-hidden="true">&times;</span>
															</button>
														</div>

														<div class="modal-body bg-white">
															<div class="bgc-white radius-1">
																<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=update_data" method="POST">
																	<div class="row col-12 mt-2 mt-md-4">
																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-building text-blue"></i>
																				สังกัด
																			</div>
																			<div class=col-md-8>
																				<select name="code_department" class="form-control col-sm-12 col-md-12">
																					<option value="0"<?= ((int) ($personnel['code_department'] ?? 0) === 0) ? ' selected' : ''; ?>>ไม่ระบุสังกัด</option>
																					<?php foreach ($department_rows as $dd) :
																						$sel = ((int) ($personnel['code_department'] ?? 0) === (int) $dd['id']) ? ' selected' : '';
																						?>
																					<option value="<?= (int) $dd['id']; ?>"<?= $sel; ?>><?= htmlspecialchars(planMasterRowLabel($dd), ENT_QUOTES, 'UTF-8'); ?></option>
																					<?php endforeach; ?>
																				</select>
																			</div>
																		</div>
																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-sitemap text-blue"></i>
																				หน่วยงาน
																			</div>
																			<div class=col-md-8>
																				<select name="code_unit" class="form-control col-sm-12 col-md-12">
																					<option value="0"<?= ((int) ($personnel['code_unit'] ?? 0) === 0) ? ' selected' : ''; ?>>ไม่ระบุหน่วยงาน</option>
																					<?php foreach ($unit_rows as $uu) :
																						$sel = ((int) ($personnel['code_unit'] ?? 0) === (int) $uu['id']) ? ' selected' : '';
																						?>
																					<option value="<?= (int) $uu['id']; ?>"<?= $sel; ?>><?= htmlspecialchars(planSetUnitOptionLabel($uu), ENT_QUOTES, 'UTF-8'); ?></option>
																					<?php endforeach; ?>
																				</select>
																			</div>
																		</div>
																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-briefcase text-blue"></i>
																				ตำแหน่ง / งาน
																			</div>
																			<div class=col-md-8>
																				<select name="code_job" class="form-control col-sm-12 col-md-12">
																					<option value="0"<?= ((int) ($personnel['code_job'] ?? 0) === 0) ? ' selected' : ''; ?>>ไม่ระบุตำแหน่ง</option>
																					<?php foreach ($job_rows as $jj) :
																						$sel = ((int) ($personnel['code_job'] ?? 0) === (int) $jj['id']) ? ' selected' : '';
																						?>
																					<option value="<?= (int) $jj['id']; ?>"<?= $sel; ?>><?= htmlspecialchars(planMasterRowLabel($jj), ENT_QUOTES, 'UTF-8'); ?></option>
																					<?php endforeach; ?>
																				</select>
																			</div>
																		</div>
																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				รายชื่อ
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $personnel['name']; ?>" required />
																			</div>
																		</div>
																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $personnelId; ?>">

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
											<?php endif; ?>

									<?php
											endforeach;
											endif;
									?>
								</tbody>
									<?php
										endforeach;
									else : ?>
								<tbody>
										<tr>
											<td colspan="7" class="text-center text-90 py-4">
												ไม่พบข้อมูลหน่วยงาน (<code>set_unit</code>) ในฐาน project หรือยังไม่มีรายชื่อ
												<br />
												<span class="text-muted">ตรวจสอบว่ามีตารางและข้อมูลหน่วยงานในฐาน project และเปิดหน้านี้ด้วย <code>?page=setting&amp;op=set_project_personnel</code></span>
											</td>
										</tr>
									<?php endif; ?>

							</table>

							<!-- ลบหน่วยงาน -->
							<div class="modal fade modal-lg" id="modal-edit-unit" tabindex="-1" role="dialog" aria-labelledby="modalEditUnitLabel" aria-hidden="true">
								<div class="modal-dialog" role="document">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title text-blue" id="modalEditUnitLabel"><i class="fas fa-edit text-secondary"></i> แก้ไขหน่วยงาน</h5>
											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>
										</div>
										<div class="modal-body bg-white">
											<form id="form-edit-unit-ajax" class="row col-12 needs-validation" novalidate="" enctype="multipart/form-data" method="POST">
												<div class="row col-12 mt-2 mt-md-4">
													<div class="col-md-12">
														<div class="mb-2 text-95 text-secondary-d2">
															หน่วยงาน: <span id="edit_unit_title" class="font-bolder text-dark-m2">-</span>
														</div>
														<table class="table table-striped-default table-borderless mb-0">
															<tr>
																<td><i class="fas fa-sitemap text-blue"></i></td>
																<td class="text-95 text-600 text-secondary-d2">ชื่อหน่วยงาน</td>
																<td class="text-dark-m3"><input type="text" class="form-control col-sm-12 col-md-12" id="edit_unit_name" name="unit_name" required /></td>
															</tr>
															<tr>
																<td><i class="fas fa-file-alt text-blue"></i></td>
																<td class="text-95 text-600 text-secondary-d2">เลขที่บันทึกข้อความ</td>
																<td class="text-dark-m3"><input type="text" class="form-control col-sm-12 col-md-12" id="edit_paper_name" name="paper_name" /></td>
															</tr>
															<tr>
																<td><i class="fas fa-hashtag text-blue"></i></td>
																<td class="text-95 text-600 text-secondary-d2">รหัสเริ่มต้น</td>
																<td class="text-dark-m3"><input type="text" class="form-control col-sm-12 col-md-12" id="edit_paper_start" name="paper_start" /></td>
															</tr>
														</table>
													</div>
												</div>
												<input type="hidden" name="unit_id" id="edit_unit_id" value="0">
												<input type="hidden" name="manage_kind" id="edit_manage_kind" value="unit">
												<div class="offset-md-4 col-md-8 text-nowrap mt-3">
													<button class="btn btn-warning btn-bold px-4" type="submit"><i class="fa fa-check mr-1"></i> บันทึกการแก้ไข</button>
													<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset"><i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น</button>
												</div>
											</form>
										</div>
										<div class="modal-footer mt-3">
											<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal"><i class="ace-icon fa fa-times"></i> Close</button>
										</div>
									</div>
								</div>
							</div>

							<!-- ลบหน่วยงาน -->
							<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-unit" tabindex="-1" role="dialog" aria-labelledby="delUnitLabel" aria-hidden="true">
								<div class="modal-dialog" role="document">
									<div class="modal-content bgc-transparent brc-danger-m2 shadow">
										<div class="modal-header py-2 bgc-danger-tp1 border-0  radius-t-1">
											<h5 class="modal-title text-white-tp1 text-110 pl-2 font-bolder" id="delUnitLabel">
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
													<h5 class="text-center text-primary-emphasis">ยืนยันการ <b><i><u>ลบหน่วยงาน</u></i></b></h5>
													<div class="text-center mt-2">
														หน่วยงาน: <span id="del_unit_title" class="font-bolder">-</span>
													</div>
													<div class="text-muted text-85 mt-2">
														การลบจะตั้งสถานะเป็น <b>99</b> และจะซ่อนรายชื่อภายใต้หน่วยงานนี้ด้วย
													</div>
												</div>
											</div>
										</div>

										<div class="modal-footer bgc-white-tp2 border-0">
											<input type="hidden" id="del_unit_id" value="0">
											<button type="button" class="btn btn-danger" id="btn-confirm-del-unit">ยืนยันลบ</button>
											<button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
										</div>
									</div>
								</div>
							</div>

							<!-- เพิ่มรายชื่อเข้าหน่วยงาน -->
							<div class="modal fade modal-lg" id="modal-add-names-to-unit" tabindex="-1" role="dialog" aria-labelledby="modalAddNamesLabel" aria-hidden="true">
								<div class="modal-dialog" role="document">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title text-blue" id="modalAddNamesLabel">
												<i class="fas fa-user-plus text-secondary"></i> เพิ่มรายชื่อเข้าหน่วยงาน
											</h5>
											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>
										</div>
										<div class="modal-body bg-white">
											<div class="bgc-white radius-1">
												<form class="row col-12 needs-validation" id="form-add-names-ajax" novalidate="" enctype="multipart/form-data" action="save_personnel_ajax.php" method="POST">
													<div class="row col-12 mt-2 mt-md-4">
														<div class="col-md-12">
															<div class="mb-2 text-95 text-secondary-d2">
																หน่วยงาน: <span id="add_names_unit_title" class="font-bolder text-dark-m2">-</span>
															</div>
															<table class="table table-striped-default table-borderless mb-0">
																<tr>
																	<td><i class="fas fa-layer-group text-blue"></i></td>
																	<td class="text-95 text-600 text-secondary-d2">รายชื่อบุคลากร</td>
																	<td class="text-dark-m3">
																		<input type="text" id="staff_search2" class="form-control col-sm-12 col-md-12" placeholder="พิมพ์ชื่อเพื่อค้นหา..." autocomplete="off" inputmode="search" />
																		<div class="text-muted mt-1" style="font-size:12px;">พิมพ์เพื่อค้นหา แล้วคลิกชื่อเพื่อเพิ่ม (เลือกได้หลายคน)</div>
																		<div id="staff_results2" class="border mt-2" style="max-height:220px; overflow:auto; border-radius:6px;"></div>
																		<div class="mt-3">
																			<div class="text-secondary-d2 text-95 mb-1">รายการที่เลือก</div>
																			<div id="staff_selected2" class="d-flex flex-wrap" style="gap:6px;"></div>
																			<div id="staff_selected_inputs2"></div>
																		</div>
																	</td>
																</tr>
															</table>
														</div>
													</div>
													<input type="hidden" name="code_unit" id="add_names_code_unit" value="0">
													<input type="hidden" name="code_job" value="0">
													<input type="hidden" name="code_department" value="0">
													<div class="offset-md-4 col-md-8 text-nowrap mt-3">
														<button class="btn btn-info btn-bold px-4" type="submit">
															<i class="fa fa-check mr-1"></i> บันทึกข้อมูล
														</button>
														<button class="btn btn-outline-lightgrey btn-bgc-white btn-bold ml-2 px-4" type="reset">
															<i class="fa fa-undo mr-1"></i> ค่าเริ่มต้น
														</button>
													</div>
												</form>
											</div>
										</div>
										<div class="modal-footer mt-3">
											<button class="btn btn-sm btn-danger pull-left" data-dismiss="modal">
												<i class="ace-icon fa fa-times"></i> Close
											</button>
										</div>
									</div>
								</div>
							</div>

							<!-- เพิ่ม -->
							<div class="modal fade modal-lg" id="modal-add-set-personnel" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
								<div class="modal-dialog " role="document">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-cog text-secondary"></i> หน่วยงาน
											</h5>

											<button type="button" class="close" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>
										</div>

										<div class="modal-body bg-white">
											<div class="bgc-white radius-1">
												<form class="row col-12 needs-validation" id="my-awesome-dropzone" novalidate="" enctype="multipart/form-data" action="?page=<?= $page; ?>&op=save_data" method="POST">
													<div class="row col-12 mt-2 mt-md-4">
														<div class="col-md-12">
															<table class="table table table-striped-default table-borderless">
																<tr>
																	<td>
																		<i class="fas fa-sitemap text-blue"></i>
																	</td>
																	<td class="text-95 text-600 text-secondary-d2">
																		ชื่อหน่วยงาน
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="unit_name" name="unit_name" required />
																	</td>
																</tr>
																<tr>
																	<td>
																		<i class="fas fa-file-alt text-blue"></i>
																	</td>
																	<td class="text-95 text-600 text-secondary-d2">
																		เลขที่บันทึกข้อความ
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="paper_name" name="paper_name" />
																	</td>
																</tr>
																<tr>
																	<td>
																		<i class="fas fa-hashtag text-blue"></i>
																	</td>
																	<td class="text-95 text-600 text-secondary-d2">
																		รหัสเริ่มต้น
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" class="form-control col-sm-12 col-md-12" id="paper_start" name="paper_start" />
																	</td>
																</tr>
																<tr>
																	<td>
																		<i class="fas fa-briefcase text-blue"></i>
																	</td>
																	<td class="text-95 text-600 text-secondary-d2">
																		สังกัดงาน
																	</td>
																	<td class="text-dark-m3">
																		<select name="code_job" class="form-control col-sm-12 col-md-12">
																			<option value="0" selected>ไม่เลือก</option>
																			<?php foreach ($job_rows as $jj) : ?>
																			<option value="<?= (int) $jj['id']; ?>"><?= htmlspecialchars(planMasterRowLabel($jj), ENT_QUOTES, 'UTF-8'); ?></option>
																			<?php endforeach; ?>
																		</select>
																	</td>
																</tr>
																<tr>
																	<td>
																		<i class="fas fa-building text-blue"></i>
																	</td>
																	<td class="text-95 text-600 text-secondary-d2">
																		สังกัดโครงสร้างหลัก
																	</td>
																	<td class="text-dark-m3">
																		<select name="code_department" class="form-control col-sm-12 col-md-12">
																			<option value="0" selected>ไม่เลือก</option>
																			<?php foreach ($department_rows as $dd) : ?>
																			<option value="<?= (int) $dd['id']; ?>"><?= htmlspecialchars(planMasterRowLabel($dd), ENT_QUOTES, 'UTF-8'); ?></option>
																			<?php endforeach; ?>
																		</select>
																	</td>
																</tr>
																<tr>
																	<td>
																		<i class="fas fa-layer-group text-blue"></i>
																	</td>
																	<td class="text-95 text-600 text-secondary-d2">
																		รายชื่อบุคลากร
																	</td>
																	<td class="text-dark-m3">
																		<input type="text" id="staff_search" class="form-control col-sm-12 col-md-12" placeholder="พิมพ์ชื่อเพื่อค้นหา..." autocomplete="off" inputmode="search" />
																		<div id="staff_search_help" class="text-muted mt-1" style="font-size:12px;">ไม่บังคับ — พิมพ์เพื่อค้นหาแล้วคลิกชื่อเพื่อเพิ่ม (เลือกได้หลายคน) หรือกำหนดทีหลังจากหน้าข้อมูลบุคลากร</div>
																		<div id="staff_results" class="border mt-2" style="max-height:220px; overflow:auto; border-radius:6px;">
																			<?php foreach ($staff_initial as $s) : ?>
																				<div role="button" tabindex="0" class="staff-result-item px-2 py-2 border-bottom" data-code="<?= htmlspecialchars($s['code'], ENT_QUOTES, 'UTF-8'); ?>" data-text="<?= htmlspecialchars($s['text'], ENT_QUOTES, 'UTF-8'); ?>" style="cursor:pointer; user-select:none;">
																					<?= htmlspecialchars($s['text'], ENT_QUOTES, 'UTF-8'); ?>
																				</div>
																			<?php endforeach; ?>
																		</div>
																		<div class="mt-3">
																			<div class="text-secondary-d2 text-95 mb-1">รายการที่เลือก</div>
																			<div id="staff_selected" class="d-flex flex-wrap" style="gap:6px;"></div>
																			<div id="staff_selected_inputs"></div>
																		</div>
																	</td>
																</tr>
															</table>
														</div>
													</div>
													<input type="hidden" name="op_from" value="add_unit_with_personnel">

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
	<script>
		(function() {
			function escapeHtml(s) {
				return String(s || '').replace(/[&<>"']/g, function(m) {
					return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]);
				});
			}

			// state ต่อโมดัล (กัน bind แล้วหาย)
			const chosen = new Map(); // code -> name

			function getRoot() {
				return $('#modal-add-set-personnel');
			}
			function getEls() {
				const $root = getRoot();
				return {
					$root,
					$input: $root.find('#staff_search'),
					$results: $root.find('#staff_results'),
					$selected: $root.find('#staff_selected'),
					$inputs: $root.find('#staff_selected_inputs')
				};
			}

			function renderChosen() {
				const { $selected, $inputs } = getEls();
				$selected.empty();
				$inputs.empty();
				for (const [code, name] of chosen.entries()) {
					const chip =
						'<span class="badge badge-primary text-90" style="padding:6px 10px; border-radius:999px;">' +
						escapeHtml(name) +
						' <span role="button" tabindex="0" class="text-white ml-1 staff-remove" data-code="' + escapeHtml(code) + '" style="cursor:pointer; font-weight:bold;">×</span>' +
						'</span>';
					$selected.append(chip);
					$inputs.append('<input type="hidden" name="staff_codes[]" value="' + escapeHtml(code) + '">');
				}
			}

			function renderResults(list) {
				const { $results } = getEls();
				$results.empty();
				if (!Array.isArray(list) || list.length === 0) {
					$results.append('<div class="text-muted p-2" style="font-size:12px;">ไม่พบรายการ</div>');
					return;
				}
				list.forEach(function(r) {
					const code = String(r.code || '');
					const text = String(r.text || '');
					const disabled = chosen.has(code);
					$results.append(
						'<div role="button" tabindex="0" class="staff-result-item px-2 py-2 border-bottom" ' +
						'data-code="' + escapeHtml(code) + '" data-text="' + escapeHtml(text) + '" ' +
						(disabled ? 'aria-disabled="true" style="opacity:0.5; cursor:not-allowed; user-select:none;"' : 'style="cursor:pointer; user-select:none;"') +
						'>' + escapeHtml(text) + '</div>'
					);
				});
			}

			let timer = null;
			function search(q) {
				const { $results } = getEls();
				const stamp = Date.now();
				$results.empty().append('<div class="text-muted p-2" style="font-size:12px;">กำลังค้นหา...</div>');
				$.ajax({
					url: 'search_staff.php',
					method: 'GET',
					dataType: 'json',
					cache: false,
					data: { q: q || '', _ts: stamp }
				}).done(function(data) {
					renderResults(data);
				}).fail(function(xhr) {
					$results.empty().append('<div class="text-danger p-2" style="font-size:12px;">โหลดรายชื่อไม่สำเร็จ (HTTP ' + xhr.status + ')</div>');
				});
			}

			// bind แบบ global เพื่อไม่พึ่ง shown.bs.modal
			$(document).off('.staffpicker2');

			$(document).on('shown.bs.modal.staffpicker2', '#modal-add-set-personnel', function() {
				// reset เมื่อเปิดโมดัล
				chosen.clear();
				renderChosen();
				search('');
				setTimeout(function() {
					const { $input } = getEls();
					$input.prop('readonly', false).prop('disabled', false).trigger('focus');
				}, 50);
			});

			$(document).on('input.staffpicker2 keyup.staffpicker2', '#modal-add-set-personnel #staff_search', function() {
				const q = $(this).val();
				clearTimeout(timer);
				timer = setTimeout(function() {
					search(q);
				}, 250);
			});

			$(document).on('click.staffpicker2 keydown.staffpicker2', '#modal-add-set-personnel .staff-result-item', function(e) {
				if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
				const code = String($(this).data('code') || '');
				const text = String($(this).data('text') || $(this).text() || '');
				if (!code || chosen.has(code)) return;
				if ($(this).attr('aria-disabled') === 'true') return;
				chosen.set(code, text);
				renderChosen();
				search($('#modal-add-set-personnel #staff_search').val());
			});

			$(document).on('click.staffpicker2 keydown.staffpicker2', '#modal-add-set-personnel .staff-remove', function(e) {
				if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
				const code = String($(this).data('code') || '');
				if (!code) return;
				chosen.delete(code);
				renderChosen();
				search($('#modal-add-set-personnel #staff_search').val());
			});
		})();
	</script>
	<script>
		// Fallback แบบไม่พึ่ง jQuery/Select2: เพื่อกันกรณีสคริปต์อื่น error แล้ว handler ไม่ถูก bind
		(function() {
			const MODAL_ID = 'modal-add-set-personnel';

			function qs(sel, root) { return (root || document).querySelector(sel); }
			function qsa(sel, root) { return Array.from((root || document).querySelectorAll(sel)); }
			function setDebug(_text) {}
			function escapeHtml(s) {
				return String(s || '').replace(/[&<>"']/g, function(m) {
					return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]);
				});
			}

			// state
			const chosen = new Map(); // code -> text

			function renderChosen() {
				const selected = qs('#' + MODAL_ID + ' #staff_selected');
				const inputs = qs('#' + MODAL_ID + ' #staff_selected_inputs');
				if (!selected || !inputs) return;
				selected.innerHTML = '';
				inputs.innerHTML = '';
				for (const [code, text] of chosen.entries()) {
					const chip = document.createElement('span');
					chip.className = 'badge badge-primary text-90';
					chip.style.padding = '6px 10px';
					chip.style.borderRadius = '999px';
					chip.innerHTML = escapeHtml(text) + ' <span role="button" tabindex="0" class="text-white ml-1 staff-remove" data-code="' + escapeHtml(code) + '" style="cursor:pointer; font-weight:bold;">×</span>';
					selected.appendChild(chip);

					const inp = document.createElement('input');
					inp.type = 'hidden';
					inp.name = 'staff_codes[]';
					inp.value = code;
					inputs.appendChild(inp);
				}
			}

			function renderResults(list) {
				const results = qs('#' + MODAL_ID + ' #staff_results');
				if (!results) return;
				results.innerHTML = '';
				if (!Array.isArray(list) || list.length === 0) {
					const empty = document.createElement('div');
					empty.className = 'text-muted p-2';
					empty.style.fontSize = '12px';
					empty.textContent = 'ไม่พบรายการ';
					results.appendChild(empty);
					return;
				}
				for (const r of list) {
					const code = String(r.code || '');
					const text = String(r.text || '');
					const row = document.createElement('div');
					row.className = 'staff-result-item px-2 py-2 border-bottom';
					row.setAttribute('role', 'button');
					row.setAttribute('tabindex', '0');
					row.dataset.code = code;
					row.dataset.text = text;
					row.textContent = text;
					if (chosen.has(code)) {
						row.style.opacity = '0.5';
						row.style.cursor = 'not-allowed';
						row.setAttribute('aria-disabled', 'true');
					} else {
						row.style.cursor = 'pointer';
					}
					results.appendChild(row);
				}
			}

			let lastController = null;
			async function doSearch(q) {
				try {
					if (lastController) lastController.abort();
					lastController = new AbortController();
					const ts = Date.now();
					setDebug('search "' + (q || '') + '" ts=' + ts);
					const url = 'search_staff.php?q=' + encodeURIComponent(q || '') + '&_ts=' + ts;
					const res = await fetch(url, { signal: lastController.signal, credentials: 'same-origin' });
					if (!res.ok) {
						setDebug('HTTP ' + res.status);
						renderResults([]);
						return;
					}
					const data = await res.json();
					setDebug('ok rows=' + (Array.isArray(data) ? data.length : 0));
					renderResults(data);
				} catch (e) {
					if (e && e.name === 'AbortError') return;
					setDebug('error ' + (e && e.message ? e.message : e));
				}
			}

			function bindOnce() {
				const modal = qs('#' + MODAL_ID);
				const input = qs('#' + MODAL_ID + ' #staff_search');
				const results = qs('#' + MODAL_ID + ' #staff_results');
				if (!modal || !input || !results) return false;
				if (modal.dataset.staffPickerBound === '1') return true;
				modal.dataset.staffPickerBound = '1';

				setDebug('bound');

				// initial load
				chosen.clear();
				renderChosen();
				doSearch('');

				let t = null;
				input.addEventListener('input', function() {
					const q = input.value || '';
					clearTimeout(t);
					t = setTimeout(() => doSearch(q), 250);
				});

				results.addEventListener('click', function(ev) {
					const el = ev.target.closest('.staff-result-item');
					if (!el) return;
					if (el.getAttribute('aria-disabled') === 'true') return;
					const code = el.dataset.code || '';
					const text = el.dataset.text || el.textContent || '';
					if (!code || chosen.has(code)) return;
					chosen.set(code, text);
					renderChosen();
					doSearch(input.value || '');
				});

				modal.addEventListener('click', function(ev) {
					const el = ev.target.closest('.staff-remove');
					if (!el) return;
					const code = el.dataset.code || '';
					if (!code) return;
					chosen.delete(code);
					renderChosen();
					doSearch(input.value || '');
				});

				// focus when modal opens (bootstrap 4/5)
				modal.addEventListener('shown.bs.modal', function() {
					setTimeout(() => input.focus(), 50);
				});

				return true;
			}

			// try bind now + retry a few times (in case modal html is injected late)
			let tries = 0;
			const iv = setInterval(function() {
				tries++;
				if (bindOnce() || tries > 20) clearInterval(iv);
			}, 300);

			// AJAX submit: บันทึกสำเร็จแล้วปิด modal
			document.addEventListener('submit', async function(ev) {
				const form = ev.target;
				if (!form) return;
				// ดักเฉพาะฟอร์มเพิ่มหน่วยงานใน modal นี้
				const isTargetForm =
					form.id === 'form-add-unit-ajax' ||
					form.getAttribute('data-unit-form') === '1' ||
					(form.closest && form.closest('#modal-add-set-personnel') && form.querySelector('input[name="op_from"][value="add_unit_with_personnel"]'));
				if (!isTargetForm) return;
				ev.preventDefault();

				const btn = form.querySelector('button[type="submit"]');
				if (btn) btn.disabled = true;
				// saving...

				try {
					const fd = new FormData(form);
					// op_from จะยังส่งมาด้วย แต่ endpoint นี้ไม่ใช้
					const url = 'save_unit_ajax.php';
					const res = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
					if (!res.ok) {
						setDebug('save HTTP ' + res.status);
						if (btn) btn.disabled = false;
						return;
					}
					const data = await res.json();
					if (!data || data.ok !== true) {
						const msg = (data && data.message) ? data.message : 'บันทึกไม่สำเร็จ';
						try {
							if (window.Swal && typeof window.Swal.fire === 'function') {
								window.Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: msg });
							} else if (typeof window.swal === 'function') {
								window.swal({ title: 'บันทึกไม่สำเร็จ', text: msg, icon: 'error' });
							} else {
								alert(msg);
							}
						} catch (e) {}
						if (btn) btn.disabled = false;
						return;
					}

					// แจ้งเตือนสำเร็จ (รองรับ SweetAlert2 และ SweetAlert v1)
					try {
						if (window.Swal && typeof window.Swal.fire === 'function') {
							window.Swal.fire({
								icon: 'success',
								title: 'บันทึกสำเร็จ',
								text: 'บันทึกข้อมูลเรียบร้อยแล้ว'
							});
						} else if (typeof window.swal === 'function') {
							window.swal({
								title: "บันทึกสำเร็จ",
								text: "บันทึกข้อมูลเรียบร้อยแล้ว",
								icon: "success"
							});
						} else {
							console.log('บันทึกสำเร็จ');
						}
					} catch (e) {
						// ห้ามให้แจ้งเตือนทำให้ flow บันทึกล้ม
						console.warn('alert error', e);
					}

					// ปิด modal (รองรับ bootstrap 4/5)
					try {
						const modal = qs('#' + MODAL_ID);
						if (modal) {
							if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
								window.jQuery(modal).modal('hide');
							} else {
								modal.classList.remove('show');
								modal.style.display = 'none';
								document.body.classList.remove('modal-open');
								const bd = document.querySelector('.modal-backdrop');
								if (bd) bd.remove();
							}
						}
					} catch (e) {
						console.warn('modal hide error', e);
					}

					// รีเฟรชหน้าเพื่อให้เห็นข้อมูลใหม่
					setTimeout(function() {
						window.location.reload();
					}, 300);
				} catch (e) {
					if (btn) btn.disabled = false;
				}
			}, true);
		})();
	</script>
	<script>
		// เพิ่มรายชื่อเข้าหน่วยงาน (modal-add-names-to-unit) + บันทึก AJAX
		(function() {
			const MODAL_ID = 'modal-add-names-to-unit';

			function qs(sel, root) { return (root || document).querySelector(sel); }
			function setDebug(_text) {}

			// set unit id/title when click button
			document.addEventListener('click', function(ev) {
				const btn = ev.target.closest('.btn-add-names-to-unit');
				if (!btn) return;
				const uid = btn.getAttribute('data-unit-id') || '0';
				const title = btn.getAttribute('data-unit-title') || '-';
				const hid = qs('#' + MODAL_ID + ' #add_names_code_unit');
				const lab = qs('#' + MODAL_ID + ' #add_names_unit_title');
				if (hid) hid.value = uid;
				if (lab) lab.textContent = title;
				chosen.clear();
				renderChosen();
				// โหลดรายการเริ่มต้น/รีเฟรชผลค้นหา (กันกรณี event shown.bs.modal ไม่ทำงาน)
				doSearch('');
			});

			// simple staff picker (reuse search_staff.php)
			const chosen = new Map();
			function escapeHtml(s) {
				return String(s || '').replace(/[&<>"']/g, function(m) {
					return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]);
				});
			}
			function renderChosen() {
				const selected = qs('#' + MODAL_ID + ' #staff_selected2');
				const inputs = qs('#' + MODAL_ID + ' #staff_selected_inputs2');
				if (!selected || !inputs) return;
				selected.innerHTML = '';
				inputs.innerHTML = '';
				for (const [code, text] of chosen.entries()) {
					const chip = document.createElement('span');
					chip.className = 'badge badge-primary text-90';
					chip.style.padding = '6px 10px';
					chip.style.borderRadius = '999px';
					chip.innerHTML = escapeHtml(text) + ' <span role="button" tabindex="0" class="text-white ml-1 staff-remove2" data-code="' + escapeHtml(code) + '" style="cursor:pointer; font-weight:bold;">×</span>';
					selected.appendChild(chip);
					const inp = document.createElement('input');
					inp.type = 'hidden';
					inp.name = 'staff_codes[]';
					inp.value = code;
					inputs.appendChild(inp);
				}
			}
			function renderResults(list) {
				const results = qs('#' + MODAL_ID + ' #staff_results2');
				if (!results) return;
				results.innerHTML = '';
				if (!Array.isArray(list) || list.length === 0) {
					results.innerHTML = '<div class="text-muted p-2" style="font-size:12px;">ไม่พบรายการ</div>';
					return;
				}
				for (const r of list) {
					const code = String(r.code || '');
					const text = String(r.text || '');
					const row = document.createElement('div');
					row.className = 'staff-result-item2 px-2 py-2 border-bottom';
					row.setAttribute('role', 'button');
					row.setAttribute('tabindex', '0');
					row.dataset.code = code;
					row.dataset.text = text;
					row.textContent = text;
					if (chosen.has(code)) {
						row.style.opacity = '0.5';
						row.style.cursor = 'not-allowed';
						row.setAttribute('aria-disabled', 'true');
					} else {
						row.style.cursor = 'pointer';
					}
					results.appendChild(row);
				}
			}
			let lastController = null;
			async function doSearch(q) {
				try {
					if (lastController) lastController.abort();
					lastController = new AbortController();
					const ts = Date.now();
					// searching...
					const unitId = (qs('#' + MODAL_ID + ' #add_names_code_unit')?.value) || '0';
					const url = 'search_staff_for_unit.php?unit_id=' + encodeURIComponent(unitId) + '&q=' + encodeURIComponent(q || '') + '&_ts=' + ts;
					const res = await fetch(url, { signal: lastController.signal, credentials: 'same-origin' });
					if (!res.ok) { renderResults([]); return; }
					const data = await res.json();
					renderResults(data);
				} catch (e) {
					if (e && e.name === 'AbortError') return;
				}
			}

			// bind modal events
			// บางหน้า event shown.bs.modal อาจไม่ยิง (ขึ้นกับ bootstrap/jQuery) จึงไม่พึ่ง event นี้เพียงอย่างเดียว
			document.addEventListener('shown.bs.modal', function(ev) {
				const modal = ev.target;
				if (!modal || modal.id !== MODAL_ID) return;
				chosen.clear();
				renderChosen();
				doSearch('');
				const input = qs('#' + MODAL_ID + ' #staff_search2');
				if (input) setTimeout(() => input.focus(), 50);
			});

			let t = null;
			document.addEventListener('input', function(ev) {
				const el = ev.target;
				if (!el || el.id !== 'staff_search2') return;
				clearTimeout(t);
				t = setTimeout(() => doSearch(el.value || ''), 250);
			});

			document.addEventListener('click', function(ev) {
				const row = ev.target.closest('#' + MODAL_ID + ' .staff-result-item2');
				if (row) {
					if (row.getAttribute('aria-disabled') === 'true') return;
					const code = row.dataset.code || '';
					const text = row.dataset.text || row.textContent || '';
					if (!code || chosen.has(code)) return;
					chosen.set(code, text);
					renderChosen();
					const input = qs('#' + MODAL_ID + ' #staff_search2');
					doSearch(input ? (input.value || '') : '');
					return;
				}
				const rm = ev.target.closest('#' + MODAL_ID + ' .staff-remove2');
				if (rm) {
					const code = rm.dataset.code || '';
					if (!code) return;
					chosen.delete(code);
					renderChosen();
					const input = qs('#' + MODAL_ID + ' #staff_search2');
					doSearch(input ? (input.value || '') : '');
				}
			});

			// submit ajax
			document.addEventListener('submit', async function(ev) {
				const form = ev.target;
				if (!form || form.id !== 'form-add-names-ajax') return;
				ev.preventDefault();
				const btn = form.querySelector('button[type="submit"]');
				if (btn) btn.disabled = true;
				// saving...
				try {
					const fd = new FormData(form);
					const res = await fetch('save_personnel_ajax.php', { method: 'POST', body: fd, credentials: 'same-origin' });
					const data = await res.json();
					if (!data || data.ok !== true) {
						const msg = (data && data.message) ? data.message : 'บันทึกไม่สำเร็จ';
						try {
							if (window.Swal && typeof window.Swal.fire === 'function') {
								window.Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: msg });
							} else if (typeof window.swal === 'function') {
								window.swal({ title: 'บันทึกไม่สำเร็จ', text: msg, icon: 'error' });
							} else {
								alert(msg);
							}
						} catch (e) {}
						if (btn) btn.disabled = false;
						return;
					}
					try {
						if (window.Swal && typeof window.Swal.fire === 'function') {
							window.Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', text: 'เพิ่มรายชื่อเรียบร้อยแล้ว' });
						} else if (typeof window.swal === 'function') {
							window.swal({ title: 'บันทึกสำเร็จ', text: 'เพิ่มรายชื่อเรียบร้อยแล้ว', icon: 'success' });
						}
					} catch (e) {}

					// hide modal
					if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
						window.jQuery('#' + MODAL_ID).modal('hide');
					}
					setTimeout(() => window.location.reload(), 300);
				} catch (e) {
					if (btn) btn.disabled = false;
				}
			}, true);
		})();
	</script>
	<script>
		// แก้ไขหน่วยงาน (ไม่แก้รายชื่อบุคลากร) แบบ AJAX
		(function() {
			function qs(sel, root) { return (root || document).querySelector(sel); }

			document.addEventListener('click', function(ev) {
				const btn = ev.target.closest('.btn-edit-unit');
				if (!btn) return;
				const uid = btn.getAttribute('data-unit-id') || '0';
				const manageKind = btn.getAttribute('data-manage-kind') || 'unit';
				const title = btn.getAttribute('data-unit-title') || '-';
				const unitName = btn.getAttribute('data-unit-name') || '';
				const paperName = btn.getAttribute('data-paper-name') || '';
				const paperStart = btn.getAttribute('data-paper-start') || '';

				const hid = qs('#edit_unit_id');
				const hkind = qs('#edit_manage_kind');
				const lab = qs('#edit_unit_title');
				const inpUnitName = qs('#edit_unit_name');
				const inpPaperName = qs('#edit_paper_name');
				const inpPaperStart = qs('#edit_paper_start');

				if (hid) hid.value = uid;
				if (hkind) hkind.value = manageKind;
				if (lab) lab.textContent = title;
				if (inpUnitName) inpUnitName.value = unitName;
				if (inpPaperName) inpPaperName.value = paperName;
				if (inpPaperStart) inpPaperStart.value = paperStart;
			});

			document.addEventListener('submit', async function(ev) {
				const form = ev.target;
				if (!form || form.id !== 'form-edit-unit-ajax') return;
				ev.preventDefault();

				const btn = form.querySelector('button[type="submit"]');
				if (btn) btn.disabled = true;
				try {
					const fd = new FormData(form);
					const manageKind = (fd.get('manage_kind') || 'unit').toString();
					const endpoint = manageKind === 'department' ? 'update_department_ajax.php' : 'update_unit_ajax.php';
					const res = await fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' });
					const data = await res.json().catch(() => null);

					if (!res.ok || !data || data.ok !== true) {
						const msg = (data && data.message) ? data.message : 'บันทึกไม่สำเร็จ';
						if (window.Swal && typeof window.Swal.fire === 'function') {
							window.Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: msg });
						} else if (typeof window.swal === 'function') {
							window.swal({ title: 'บันทึกไม่สำเร็จ', text: msg, icon: 'error' });
						} else {
							alert(msg);
						}
						if (btn) btn.disabled = false;
						return;
					}

					if (window.Swal && typeof window.Swal.fire === 'function') {
						window.Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', text: 'แก้ไขหน่วยงานเรียบร้อยแล้ว' });
					} else if (typeof window.swal === 'function') {
						window.swal({ title: 'บันทึกสำเร็จ', text: 'แก้ไขหน่วยงานเรียบร้อยแล้ว', icon: 'success' });
					}

					if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
						window.jQuery('#modal-edit-unit').modal('hide');
					}
					setTimeout(() => window.location.reload(), 300);
				} catch (e) {
					if (btn) btn.disabled = false;
				}
			}, true);
		})();
	</script>
	<script>
		// ลบหน่วยงาน (status=99) แบบ AJAX
		(function() {
			function qs(sel, root) { return (root || document).querySelector(sel); }

			document.addEventListener('click', function(ev) {
				const btn = ev.target.closest('.btn-del-unit');
				if (!btn) return;
				const uid = btn.getAttribute('data-unit-id') || '0';
				const title = btn.getAttribute('data-unit-title') || '-';
				const hid = qs('#del_unit_id');
				const lab = qs('#del_unit_title');
				if (hid) hid.value = uid;
				if (lab) lab.textContent = title;
			});

			document.addEventListener('click', async function(ev) {
				const btn = ev.target.closest('#btn-confirm-del-unit');
				if (!btn) return;
				const unitId = (qs('#del_unit_id')?.value) || '0';
				if (!unitId || unitId === '0') return;
				btn.disabled = true;
				try {
					const fd = new FormData();
					fd.append('unit_id', unitId);
					const res = await fetch('delete_unit_ajax.php', { method: 'POST', body: fd, credentials: 'same-origin' });
					const data = await res.json().catch(() => null);
					if (!res.ok || !data || data.ok !== true) {
						const msg = (data && data.message) ? data.message : 'ลบไม่สำเร็จ';
						if (window.Swal && typeof window.Swal.fire === 'function') {
							window.Swal.fire({ icon: 'error', title: 'ลบไม่สำเร็จ', text: msg });
						} else if (typeof window.swal === 'function') {
							window.swal({ title: 'ลบไม่สำเร็จ', text: msg, icon: 'error' });
						} else {
							alert(msg);
						}
						btn.disabled = false;
						return;
					}

					if (window.Swal && typeof window.Swal.fire === 'function') {
						window.Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', text: 'ลบหน่วยงานเรียบร้อยแล้ว' });
					} else if (typeof window.swal === 'function') {
						window.swal({ title: 'ลบสำเร็จ', text: 'ลบหน่วยงานเรียบร้อยแล้ว', icon: 'success' });
					}

					if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
						window.jQuery('#modal-del-unit').modal('hide');
					}
					setTimeout(() => window.location.reload(), 300);
				} catch (e) {
					btn.disabled = false;
				}
			});
		})();
	</script>
	<script>
		(function() {
			function closest(el, sel) {
				while (el && el !== document) {
					if (el.matches && el.matches(sel)) return el;
					el = el.parentElement;
				}
				return null;
			}
			document.addEventListener('click', function(ev) {
				var hit = closest(ev.target, '[data-unit-toggle]');
				if (!hit) return;
				ev.preventDefault();
				var tbody = closest(hit, '.plan-unit-group');
				if (!tbody) return;
				var expanded = tbody.classList.toggle('is-expanded');
				hit.setAttribute('aria-expanded', expanded ? 'true' : 'false');
				var rows = tbody.querySelectorAll('.unit-personnel-subrow');
				for (var i = 0; i < rows.length; i++) {
					rows[i].classList.toggle('d-none', !expanded);
				}
			});
			document.addEventListener('keydown', function(ev) {
				if (ev.key !== 'Enter' && ev.key !== ' ') return;
				var hit = closest(ev.target, '[data-unit-toggle]');
				if (!hit) return;
				ev.preventDefault();
				hit.click();
			});

			function applyUnitSearch() {
				var searchEl = document.getElementById('plan-unit-table-search');
				var q = '';
				if (searchEl && searchEl.value) {
					q = String(searchEl.value).trim().toLowerCase();
				}
				var groups = document.querySelectorAll('.plan-unit-group');
				for (var g = 0; g < groups.length; g++) {
					var tb = groups[g];
					var hay = tb.getAttribute('data-unit-search-text') || '';
					var show = !q || hay.indexOf(q) !== -1;
					tb.style.display = show ? '' : 'none';
				}
			}
			var searchInp = document.getElementById('plan-unit-table-search');
			if (searchInp) {
				searchInp.addEventListener('input', applyUnitSearch);
				searchInp.addEventListener('search', applyUnitSearch);
			}

			document.addEventListener('click', function(ev) {
				var btn = closest(ev.target, '.btn-unit-admin-toggle');
				if (!btn) return;
				ev.preventDefault();
				var pid = btn.getAttribute('data-personnel-id') || '0';
				var staffId = btn.getAttribute('data-staff-id') || '0';
				var codeUnit = btn.getAttribute('data-code-unit') || '0';
				var unitCode = (btn.getAttribute('data-unit-code') || '').trim();
				var manageCd = parseInt(btn.getAttribute('data-manage-code-department') || '0', 10);
				var manageCj = parseInt(btn.getAttribute('data-manage-code-job') || '0', 10);
				var noUnitHint = (!codeUnit || codeUnit === '0') && !unitCode && !manageCd && !manageCj;
				if ((!pid || pid === '0') && (!staffId || staffId === '0' || noUnitHint)) {
					return;
				}
				function refreshUnitAdminSummary(fromBtn) {
					var group = closest(fromBtn, '.plan-unit-group');
					if (!group) return;
					var names = [];
					var seen = {};
					var toggles = group.querySelectorAll('.btn-unit-admin-toggle');
					for (var i = 0; i < toggles.length; i++) {
						if (toggles[i].getAttribute('aria-pressed') !== 'true') continue;
						var nm = (toggles[i].getAttribute('data-person-name') || '').trim();
						if (!nm || seen[nm]) continue;
						seen[nm] = true;
						names.push(nm);
					}
					var box = group.querySelector('.unit-admin-summary');
					var namesEl = group.querySelector('.unit-admin-summary-names');
					if (!box || !namesEl) return;
					if (names.length) {
						namesEl.textContent = names.join(' · ');
						box.classList.remove('d-none');
					} else {
						namesEl.textContent = '';
						box.classList.add('d-none');
					}
				}
				var fd = new FormData();
				fd.append('personnel_id', pid);
				if (!pid || pid === '0') {
					fd.append('staff_id', staffId);
					fd.append('code_unit', codeUnit);
					if (unitCode) {
						fd.append('unit_code', unitCode);
					}
					fd.append('code_department', btn.getAttribute('data-manage-code-department') || '0');
					fd.append('code_job', btn.getAttribute('data-manage-code-job') || '0');
				}
				btn.disabled = true;
				fetch('toggle_personnel_unit_admin_ajax.php', { method: 'POST', body: fd, credentials: 'same-origin' })
					.then(function(r) { return r.json(); })
					.then(function(data) {
						btn.disabled = false;
						if (!data || data.ok !== true) {
							var msg = (data && data.message) ? data.message : 'บันทึกไม่สำเร็จ';
							if (window.Swal && typeof window.Swal.fire === 'function') {
								window.Swal.fire({ icon: 'error', title: msg });
							} else {
								alert(msg);
							}
							return;
						}
						var on = parseInt(data.is_unit_admin, 10) === 1;
						btn.setAttribute('aria-pressed', on ? 'true' : 'false');
						var icon = btn.querySelector('i');
						if (icon) {
							if (on) {
								icon.classList.add('text-warning');
								icon.classList.remove('text-muted');
								icon.style.opacity = '';
							} else {
								icon.classList.remove('text-warning');
								icon.classList.add('text-muted');
								icon.style.opacity = '0.45';
							}
						}
						btn.title = on
							? 'เป็นผู้ดูแลหน่วยงานแล้ว — คลิกเพื่อถอดสิทธิ์'
							: 'คลิกเพื่อตั้งเป็นผู้ดูแลหน่วยงาน (Admin)';
						refreshUnitAdminSummary(btn);
						var okMsg = on ? 'บันทึกเรียบร้อย: ตั้งผู้ดูแลหน่วยงานแล้ว' : 'บันทึกเรียบร้อย: ถอดสิทธิ์ผู้ดูแลแล้ว';
						if (window.Swal && typeof window.Swal.fire === 'function') {
							window.Swal.fire({
								icon: 'success',
								title: okMsg,
								timer: on ? 900 : 1200,
								showConfirmButton: !on
							}).then(function() {
								// เปิดสิทธิ์แล้วมีการถอด Admin คนอื่นในขอบเขตเดียวกัน — รีโหลดให้ไอคอนทุกแถวตรงกับฐานข้อมูล
								if (on) {
									window.location.reload();
								}
							});
						} else {
							alert(okMsg);
							if (on) {
								window.location.reload();
							}
						}
					})
					.catch(function() {
						btn.disabled = false;
					});
			});
		})();
	</script>
	<?php }  //set personnel
	?>

	<?php function set_project_personnel_info()
	{
		global $db_Project, $db_person;
		$page = $_GET['page'];

		// รายชื่อทุกคนจากฐาน person (staff) ที่ work_status = 0
		$staffList = [];
		$sqlAllStaff = mysqli_query(
			$db_person,
			"SELECT * FROM staff WHERE work_status = '0' OR work_status = 0 ORDER BY fname ASC, lname ASC, code ASC"
		);
		if ($sqlAllStaff) {
			while ($row = mysqli_fetch_assoc($sqlAllStaff)) {
				$staffList[] = $row;
			}
		}

		// หน่วยงานร่วมจาก project (ถ้ามีการผูก staff_id ใน set_project_personnel)
		$unitsByStaffId = [];
		$jointUnitIdsByStaffId = [];
		try {
			ensureSetProjectPersonnelTable($db_Project);
			$sqlUnits = mysqli_query(
				$db_Project,
				"SELECT p.staff_id, p.code_unit, u.name AS unit_name, u.job_code
				 FROM set_project_personnel p
				 LEFT JOIN set_unit u ON u.id = p.code_unit AND (u.status IS NULL OR u.status != '99')
				 WHERE p.status != '99' AND p.staff_id IS NOT NULL AND p.staff_id > 0"
			);
			if ($sqlUnits) {
				while ($ur = mysqli_fetch_assoc($sqlUnits)) {
					$sid = (int)($ur['staff_id'] ?? 0);
					$un = trim((string)($ur['unit_name'] ?? ''));
					if ($sid <= 0 || $un === '') {
						continue;
					}
					// แสดงเฉพาะหน่วยงานกลุ่มที่ 2 (ชุดเดียวกับหน้าหน่วยงาน) — ไม่โชว์หน่วยเก่าที่ผูก job
					if (function_exists('planSetUnitGroupNumber') && planSetUnitGroupNumber([
						'job_code' => (string)($ur['job_code'] ?? ''),
						'code_job' => 0,
					]) !== 2) {
						continue;
					}
					if (!isset($unitsByStaffId[$sid])) {
						$unitsByStaffId[$sid] = [];
					}
					$unitsByStaffId[$sid][$un] = true;
					$cuid = (int)($ur['code_unit'] ?? 0);
					if ($cuid > 0) {
						if (!isset($jointUnitIdsByStaffId[$sid])) {
							$jointUnitIdsByStaffId[$sid] = [];
						}
						$jointUnitIdsByStaffId[$sid][$cuid] = true;
					}
				}
			}
		} catch (Throwable $e) {
			@error_log('set_project_personnel_info units map: ' . $e->getMessage());
		}

		$unit_rows_for_joint = [];
		try {
			// เลือกหน่วยงานร่วมได้เฉพาะแบบใหม่ (ไม่ผูก job)
			if (function_exists('getSetUnitGroupedForPlan')) {
				$__g = getSetUnitGroupedForPlan($db_Project);
				$unit_rows_for_joint = is_array($__g[2] ?? null) ? $__g[2] : [];
			} else {
				$unit_rows_for_joint = getSetUnitListForPlan($db_Project);
			}
		} catch (Throwable $e) {
			@error_log('set_project_personnel_info unit list: ' . $e->getMessage());
		}
	?>
		<div class="row mt-4">
			<div class="col-12 col-lg-12">
				<div class="bcard card h-100 pt-1 pt-lg-2">
					<div class="card-header border-0">
						<h5 class="card-title text-dark-m2 pl-1 text-110">
							<i class="fas fa-id-badge text-blue"></i> ข้อมูลบุคลากร
						</h5>
						<div class="card-toolbar align-self-center">
							<a href="#" data-action="reload" class="card-toolbar-btn text-blue text-105">
								<i class="fas fa-sync-alt"></i>
							</a>
						</div>
					</div>
					<div class="card-body p-0 personnel-info-dt-wrap">
						<div class="table-responsive-md">
							<table id="table-personnel-info" class="table table-border-x brc-secondary-l3 mb-0" style="width:100%">
								<thead>
									<tr class="text-80 text-default-d3 bgc-default-l4">
										<th class="pl-2 pl-lg-4">ลำดับที่</th>
										<th>ชื่อ-นามสกุล</th>
										<th>หน่วยงาน <span class="text-muted text-75 font-normal">(กำหนดจากรายการที่สร้าง)</span></th>
									</tr>
								</thead>
								<tbody>
									<?php if (!empty($staffList)) : ?>
										<?php $i = 0;
										foreach ($staffList as $srow) :
											$i++;
											$sid = (int)($srow['id'] ?? 0);
											$fname = trim((string)($srow['fname'] ?? ''));
											$lname = trim((string)($srow['lname'] ?? ''));
											$fullName = trim($fname . ' ' . $lname);
											$unitList = isset($unitsByStaffId[$sid]) ? array_keys($unitsByStaffId[$sid]) : [];
											sort($unitList, SORT_LOCALE_STRING);
											$staffCodeJoint = trim((string)($srow['code'] ?? ''));
											$excludeUnitIds = isset($jointUnitIdsByStaffId[$sid])
												? array_keys($jointUnitIdsByStaffId[$sid])
												: [];
									?>
											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4"><?= $i; ?></td>
												<td class="text-dark-m2 text-85"><?= htmlspecialchars($fullName !== '' ? $fullName : '-', ENT_QUOTES, 'UTF-8'); ?></td>
												<td class="text-dark-m2 text-85">
													<div class="d-flex align-items-start">
														<div class="flex-grow-1 pr-1" style="min-width:0;">
															<?php if (!empty($unitList)) : ?>
																<ul class="mb-0 pl-3" style="margin:0; padding-left:1.1rem;">
																	<?php foreach ($unitList as $un) : ?>
																		<li class="text-85"><?= htmlspecialchars($un, ENT_QUOTES, 'UTF-8'); ?></li>
																	<?php endforeach; ?>
																</ul>
															<?php else : ?>
																<span class="text-muted text-80">ยังไม่ได้กำหนด</span>
															<?php endif; ?>
														</div>
														<?php if ($staffCodeJoint !== '') : ?>
															<button type="button"
																class="btn btn-sm btn-outline-info border-0 p-1 ml-1 btn-add-joint-unit"
																style="flex-shrink:0;line-height:1;"
																data-toggle="modal"
																data-target="#modal-add-joint-unit-from-info"
																data-staff-code="<?= htmlspecialchars($staffCodeJoint, ENT_QUOTES, 'UTF-8'); ?>"
																data-staff-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
																data-exclude-unit-ids="<?= htmlspecialchars(implode(',', $excludeUnitIds), ENT_QUOTES, 'UTF-8'); ?>"
																title="กำหนดหน่วยงาน">
																<i class="fas fa-plus-circle text-110"></i>
															</button>
														<?php endif; ?>
													</div>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php else : ?>
										<tr class="bgc-h-secondary-l4">
											<td colspan="3" class="text-center text-90 text-muted py-3">
												ไม่พบข้อมูลบุคลากร (work_status = 0)
											</td>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php if (!empty($staffList)) : ?>
		<div class="modal fade" id="modal-add-joint-unit-from-info" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header py-2">
						<h5 class="modal-title text-blue"><i class="fas fa-plus-circle text-secondary"></i> กำหนดหน่วยงาน</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body">
						<form id="form-add-joint-unit-from-info" method="post" action="save_personnel_ajax.php">
							<input type="hidden" name="code_job" value="0">
							<input type="hidden" name="code_department" value="0">
							<input type="hidden" name="staff_codes[]" id="joint_unit_staff_code_hidden" value="">
							<div class="mb-2 text-95 text-secondary-d2">
								บุคลากร: <span id="joint_unit_staff_label" class="font-bolder text-dark-m2">-</span>
							</div>
							<label class="text-95 text-600 text-secondary-d2 d-block mb-1">เลือกหน่วยงาน (จากรายการที่สร้าง)</label>
							<select name="code_unit" class="form-control" required>
								<option value="0">— เลือกหน่วยงาน —</option>
								<?php foreach ($unit_rows_for_joint as $ju) : ?>
									<option value="<?= (int)($ju['id'] ?? 0); ?>"><?= htmlspecialchars(planSetUnitOptionLabel($ju), ENT_QUOTES, 'UTF-8'); ?></option>
								<?php endforeach; ?>
							</select>
						</form>
					</div>
					<div class="modal-footer py-2">
						<button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">ยกเลิก</button>
						<button type="button" class="btn btn-info btn-sm" id="btn-save-joint-unit-from-info"><i class="fa fa-check mr-1"></i> บันทึก</button>
					</div>
				</div>
			</div>
		</div>
		<style>
			.personnel-info-dt-wrap .dataTables_wrapper .dataTables_filter input {
				margin-left: .5rem;
				border-radius: .25rem;
				border: 1px solid #dee2e6;
				padding: .25rem .5rem;
			}
			.personnel-info-dt-wrap .dataTables_wrapper .dataTables_length select {
				margin: 0 .35rem;
				display: inline-block;
				width: auto;
			}
		</style>
		<script>
			window.addEventListener('load', function () {
				function initPersonnelInfoDt() {
					if (typeof jQuery === 'undefined' || typeof jQuery.fn.DataTable !== 'function') {
						return;
					}
					var $t = jQuery('#table-personnel-info');
					if (!$t.length || jQuery.fn.dataTable.isDataTable($t)) {
						return;
					}
					$t.DataTable({
						ordering: true,
						order: [[1, 'asc']],
						pageLength: 25,
						lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'ทั้งหมด']],
						columnDefs: [
							{ targets: 0, orderable: false, searchable: false }
						],
						language: {
							decimal: '',
							emptyTable: 'ไม่มีข้อมูล',
							info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ',
							infoEmpty: 'ไม่มีรายการ',
							infoFiltered: '(กรองจากทั้งหมด _MAX_ รายการ)',
							lengthMenu: 'แสดง _MENU_ แถว',
							loadingRecords: 'กำลังโหลด…',
							processing: 'กำลังประมวลผล…',
							search: 'ค้นหา:',
							zeroRecords: 'ไม่พบข้อมูลที่ค้นหา',
							paginate: {
								first: 'หน้าแรก',
								last: 'หน้าสุดท้าย',
								next: 'ถัดไป',
								previous: 'ก่อนหน้า'
							}
						},
						drawCallback: function () {
							var api = this.api();
							var info = api.page.info();
							api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
								cell.innerHTML = info.start + i + 1;
							});
						}
					});
				}
				initPersonnelInfoDt();

				jQuery('#modal-add-joint-unit-from-info').on('show.bs.modal', function (e) {
					var btn = jQuery(e.relatedTarget);
					if (!btn || !btn.length) return;
					jQuery('#joint_unit_staff_code_hidden').val(btn.data('staff-code') || '');
					jQuery('#joint_unit_staff_label').text(btn.data('staff-name') || '');
					var sel = jQuery('#form-add-joint-unit-from-info select[name="code_unit"]');
					sel.val('0');
					var raw = btn.attr('data-exclude-unit-ids') || '';
					var exclude = {};
					if (typeof raw === 'string' && raw !== '') {
						raw.split(',').forEach(function (id) {
							id = String(id).trim();
							if (id !== '') {
								exclude[id] = true;
							}
						});
					}
					sel.find('option').each(function () {
						var opt = jQuery(this);
						var v = opt.attr('value');
						if (v === '0' || v === '') {
							opt.prop('disabled', false);
							return;
						}
						opt.prop('disabled', exclude[v] === true);
					});
				});

				jQuery('#btn-save-joint-unit-from-info').on('click', function () {
					var form = document.getElementById('form-add-joint-unit-from-info');
					if (!form) return;
					var sel = form.querySelector('select[name="code_unit"]');
					if (!sel || sel.value === '0' || sel.value === '') {
						if (window.Swal && typeof window.Swal.fire === 'function') {
							window.Swal.fire({ icon: 'warning', title: 'กรุณาเลือกหน่วยงาน' });
						} else {
							alert('กรุณาเลือกหน่วยงาน');
						}
						return;
					}
					var btn = this;
					btn.disabled = true;
					var fd = new FormData(form);
					fetch('save_personnel_ajax.php', { method: 'POST', body: fd, credentials: 'same-origin' })
						.then(function (r) { return r.json(); })
						.then(function (data) {
							if (data && data.ok === true) {
								if (window.jQuery && window.jQuery.fn.modal) {
									window.jQuery('#modal-add-joint-unit-from-info').modal('hide');
								}
								var reloadPage = function () {
									window.location.reload();
								};
								if (window.Swal && typeof window.Swal.fire === 'function') {
									window.Swal.fire({
										icon: 'success',
										title: 'บันทึกสำเร็จ',
										text: 'กำหนดหน่วยงานเรียบร้อยแล้ว'
									}).then(reloadPage);
								} else if (typeof window.swal === 'function') {
									window.swal({
										title: 'บันทึกสำเร็จ',
										text: 'กำหนดหน่วยงานเรียบร้อยแล้ว',
										icon: 'success'
									}, reloadPage);
								} else {
									alert('บันทึกสำเร็จ — กำหนดหน่วยงานเรียบร้อยแล้ว');
									reloadPage();
								}
								return;
							}
							btn.disabled = false;
							var msg = (data && data.message) ? data.message : 'บันทึกไม่สำเร็จ';
							if (window.Swal && typeof window.Swal.fire === 'function') {
								window.Swal.fire({ icon: 'error', title: msg });
							} else if (typeof window.swal === 'function') {
								window.swal({ title: msg, icon: 'error' });
							} else {
								alert(msg);
							}
						})
						.catch(function () {
							btn.disabled = false;
						});
				});
			});
		</script>
		<?php endif; ?>
	<?php }  // set_project_personnel_info
	?>

	<?php function set_project_consistency()
	{
		global $db_Project, $db_person;
		$page = $_GET['page'];
		$op_from = $_GET['op'];
	?>
		<div class="row mt-4">
			<!-- orders table -->
			<div class="col-12 col-lg-12">
				<div class="bcard card h-100 pt-1 pt-lg-2">

					<div class="card-header border-0">
						<h5 class="card-title text-dark-m2 pl-1 text-110">
							<i class="fas fa-cog text-blue"></i> ความสอดคล้อง
						</h5>
						<div class="card-toolbar align-self-center no-border">
							<style>
								.consistency-sub-list .icon-btn { line-height: 1; vertical-align: middle; }
								.consistency-sub-list .icon-btn i { font-size: 0.85rem; }
								.consistency-sub-list > li { margin-bottom: 0.2rem; line-height: 1.35; }
							</style>
							<div class="dropdown dd-backdrop dd-backdrop-none-md">
								<a class="btn btn-blue text-600 btn-xs mr-1" href="#modal-add-set-consistency" role="button" data-toggle="modal">
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
											ความสอดคล้อง
										</th>
										<th>
											รายการย่อย
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
									$consistency_data = getSetConsistencyList($db_Project, $id);

									if (!empty($consistency_data)) :
										foreach ($consistency_data as $i => $consistency) :

									?>

											<tr class="bgc-h-secondary-l4">
												<td class="pl-2 pl-lg-4">

													<?php echo $i + 1; ?>

												</td>



												<td class=" text-dark-m2 text-85">
													<?php echo htmlspecialchars($consistency['name']); ?>
													<a class="btn btn-green  btn-xs mr-1" href="#modal-add-consistency-detail<?= $consistency['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-plus"></i> เพิ่มรายการย่อย
													</a>
												</td>

												<td class="text-dark-m2 text-85">
													<?php
													$consistency_detail_data = getSetConsistencyDetailList($db_Project, $consistency['id']);

													if (!empty($consistency_detail_data)) {
														echo '<ul class="list-unstyled text-dark-m2 mb-0 consistency-sub-list">';
														foreach ($consistency_detail_data as $consistency_detail) {
															echo '<li><i class="fa fa-circle text-secondary mr-2"></i>'
																. htmlspecialchars($consistency_detail['name']);
													?>
															<button type="button"
																class="btn btn-link p-0 icon-btn text-warning edit"
																title="แก้ไขข้อมูล"
																data-target="#modal-edit-consistency-detail-project<?= $consistency_detail['id']; ?>"
																data-toggle="modal">
																<i class="fa fa-edit"></i>
															</button>

															<!-- 🔹 ปุ่มลบ -->
															<button type="button"
																class="btn btn-link p-0 ml-1 icon-btn text-danger delete"
																title="ลบข้อมูล"
																data-target="#modal-del-consistency-detail-project<?= $consistency_detail['id']; ?>"
																data-toggle="modal">
																<i class="fa fa-trash"></i>
															</button>

															<!-- with white backdrop -->
															<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-consistency-detail-project<?= $consistency_detail['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
																			<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $consistency_detail['id']; ?>" method="POST">

																				<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																				<input type="hidden" name="tb_data" value="set_project_consistency_detail">

																				<button class="btn btn-danger" type="submit">ยืนยัน </button>

																			</form>
																		</div>
																	</div>
																</div>
															</div>
															<!-- ลบ -->
															<!-- แก้ไข รายละเอียด -->
															<div class="modal fade modal-lg" id="modal-edit-consistency-detail-project<?= $consistency_detail['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

																<div class="modal-dialog " role="document">
																	<div class="modal-content">

																		<div class="modal-header">
																			<h5 class="modal-title text-blue" id="exampleModalLabel2">
																				<i class="fas fa-cog text-secondary"></i> แก้ไขรายการย่อย ความสอดคล้อง <i class="fas fa-angle-double-right" style="color: #bd9c22ff;"></i> <?= $consistency['name']; ?>
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
																							<div class=col-md-3>
																								<i class="fas fa-layer-group text-blue"></i>
																								ชื่อรายการ
																							</div>
																							<div class=col-md-9>
																								<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= htmlspecialchars($consistency_detail['name']); ?>" required />

																							</div>
																						</div>


																					</div>
																					<input type="hidden" name="op_from" value="consistency_detail">
																					<input type="hidden" name="id" value="<?= $consistency['id']; ?>">
																					<input type="hidden" name="consistency_id" value="<?= $consistency_detail['id']; ?>">

																					<div class="offset-md-4 col-md-8 text-nowrap mt-3">
																						<button class="btn btn-warning btn-bold px-4" type="submit">
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
															<!-- แก้ไข รายละเอียด -->


													<?php echo	'</li>';
														}
														echo '</ul>';
													} else {
														echo '<span class="text-muted">-</span>';
													}
													?>
												</td>


												<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
													<label>
														<input type="checkbox" name="status" class="ace-switch"
															data-id="<?= $consistency['id'] ?>" data-type="set_consistency"
															<?php if ($consistency['status'] == '1') echo "checked"; ?> />
													</label>



												</td>

												<td class="text-secondary-d3 text-95">
													<a class="btn btn-warning text-600 btn-xs mr-1" href="#modal-edit-set-consistency<?= $consistency['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-pen"></i>
														แก้ไข

													</a>
													<a class="btn btn-danger text-600 btn-xs mr-1" href="#modal-del-set-consistency<?= $consistency['id']; ?>" role="button" data-toggle="modal">
														<i class="fas fa-trash"></i>
														ลบ

													</a>

												</td>
											</tr>


											<!-- with white backdrop -->
											<div class="modal fade" data-backdrop-bg="bgc-white" id="modal-del-set-consistency<?= $consistency['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="dangerModalLabel" aria-hidden="true">
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
															<form action="?page=<?= $page; ?>&op=del_set_all&id=<?= $consistency['id']; ?>" method="POST">
																<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																<input type="hidden" name="tb_data" value="set_project_expexnses">
																<button class="btn btn-danger" type="submit">ยืนยัน </button>

															</form>
														</div>
													</div>
												</div>
											</div>
											<!-- แก้ไข -->
											<div class="modal fade modal-lg" id="modal-edit-set-consistency<?= $consistency['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> ความสอดคล้อง
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
																				ความสอดคล้อง
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" value="<?= $consistency['name']; ?>" required />

																			</div>
																		</div>

																	</div>
																	<input type="hidden" name="op_from" value="<?= $op_from; ?>">
																	<input type="hidden" name="id" value="<?= $consistency['id']; ?>">

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
											<div class="modal fade modal-lg" id="modal-add-consistency-detail<?= $consistency['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

												<div class="modal-dialog " role="document">
													<div class="modal-content">

														<div class="modal-header">
															<h5 class="modal-title text-blue" id="exampleModalLabel2">
																<i class="fas fa-cog text-secondary"></i> เพิ่มรายการย่อย ความสอดคล้อง <i class="fas fa-angle-double-right" style="color: #bd9c22ff;"></i> <?= $consistency['name']; ?>
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


																		<div class="row col-md-12 mt-3">
																			<div class=col-md-4>
																				<i class="fas fa-layer-group text-blue"></i>
																				ชื่อรายการ
																			</div>
																			<div class=col-md-8>
																				<input type="text" class="form-control col-sm-12 col-md-12" id="id-form-field-1" name="name" required />

																			</div>
																		</div>


																	</div>
																	<input type="hidden" name="op_from" value="consistency_detail">
																	<input type="hidden" name="id" value="<?= $consistency['id']; ?>">

																	<div class="offset-md-4 col-md-8 text-nowrap mt-3">
																		<button class="btn btn-warning btn-bold px-4" type="submit">
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
											<!-- จบ รายละเอียด -->

										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="5" style="text-align:center;">ไม่พบข้อมูล</td>
										</tr>
									<?php endif; ?>

								</tbody>
							</table>
							<!-- เพิ่ม -->
							<div class="modal fade modal-lg" id="modal-add-set-consistency" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">

								<div class="modal-dialog " role="document">
									<div class="modal-content">

										<div class="modal-header">
											<h5 class="modal-title text-blue" id="exampleModalLabel2">
												<i class="fas fa-cog text-secondary"></i> ความสอดคล้อง
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
																		ความสอดคล้อง
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
	<?php }  //set consistency
	?>

	<?php
	switch ($op) {
		case "set_Project_Type":
			set_Project_Type();
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
		case "set_project_personnel":
			try {
				set_project_personnel();
			} catch (Throwable $e) {
				@error_log('set_project_personnel: ' . $e->getMessage());
				echo '<div class="alert alert-danger mt-3 mx-3">โหลดหน้าหน่วยงานไม่สำเร็จ กรุณาตรวจสอบตาราง set_unit / set_job / set_department / set_project_personnel ในฐาน project</div>';
			}
			break;
		case "set_project_personnel_info":
			try {
				set_project_personnel_info();
			} catch (Throwable $e) {
				@error_log('set_project_personnel_info: ' . $e->getMessage());
				echo '<div class="alert alert-danger mt-3 mx-3">โหลดหน้าข้อมูลบุคลากรไม่สำเร็จ กรุณาลองใหม่อีกครั้ง</div>';
			}
			break;
		case "set_project_consistency":
			set_project_consistency();
			break;

		case "save_unit_ajax":
			// บันทึกเพิ่มหน่วยงาน + รายชื่อบุคลากร (AJAX)
			header('Content-Type: application/json; charset=utf-8');
			try {
				global $db_Project, $db_person;
				$status = '1';

				$unit_name = mysqli_real_escape_string($db_Project, trim((string)($_POST['unit_name'] ?? '')));
				$code_department = isset($_POST['code_department']) ? (int) $_POST['code_department'] : 0;
				$code_job = isset($_POST['code_job']) ? (int) $_POST['code_job'] : 0;
				$staff_codes = $_POST['staff_codes'] ?? [];
				if (!is_array($staff_codes)) {
					$staff_codes = [$staff_codes];
				}
				$staff_codes = array_values(array_filter(array_map(function ($v) {
					return trim((string)$v);
				}, $staff_codes), function ($v) {
					return $v !== '';
				}));

				if ($unit_name === '') {
					echo json_encode(['ok' => false, 'message' => 'กรุณากรอกชื่อหน่วยงาน'], JSON_UNESCAPED_UNICODE);
					exit;
				}

				$vcd = $code_department > 0 ? "'" . (int)$code_department . "'" : 'NULL';
				$vcj = $code_job > 0 ? "'" . (int)$code_job . "'" : 'NULL';

				$sqlUnit = mysqli_query($db_Project, "INSERT INTO set_unit(name,status) VALUE('$unit_name','$status')");
				if (!$sqlUnit) {
					echo json_encode(['ok' => false, 'message' => 'บันทึกหน่วยงานไม่สำเร็จ'], JSON_UNESCAPED_UNICODE);
					exit;
				}
				$new_unit_id = (int)mysqli_insert_id($db_Project);

				if (!empty($staff_codes)) {
					$codesEsc = array_map(function ($c) use ($db_person) {
						return mysqli_real_escape_string($db_person, (string)$c);
					}, $staff_codes);
					$in = "'" . implode("','", $codesEsc) . "'";

					$personnelNameMap = [];
					$sqlStaff = mysqli_query(
						$db_person,
						"SELECT code, CONCAT(TRIM(fname),' ',TRIM(lname)) AS full_name
						 FROM staff
						 WHERE work_status IN ('0','1') AND code IN ($in)"
					);
					if ($sqlStaff) {
						while ($r = mysqli_fetch_assoc($sqlStaff)) {
							$personnelNameMap[(string)($r['code'] ?? '')] = trim((string)($r['full_name'] ?? ''));
						}
					}

					foreach ($staff_codes as $staff_code_raw) {
						$pName = trim((string)($personnelNameMap[(string)$staff_code_raw] ?? ''));
						if ($pName === '') continue;
						$pNameEsc = mysqli_real_escape_string($db_Project, $pName);
						mysqli_query(
							$db_Project,
							"INSERT INTO set_project_personnel(name,code_department,code_job,code_unit,status)
							 VALUES('$pNameEsc',$vcd,$vcj,'$new_unit_id','$status')"
						);
					}
				}

				echo json_encode(['ok' => true, 'unit_id' => $new_unit_id], JSON_UNESCAPED_UNICODE);
			} catch (Throwable $e) {
				@error_log('save_unit_ajax: ' . $e->getMessage());
				echo json_encode(['ok' => false, 'message' => 'เกิดข้อผิดพลาดระหว่างบันทึก'], JSON_UNESCAPED_UNICODE);
			}
			exit;

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
				global $db_pbmBudget_PBM;
				$name = trim((string)($_POST['name'] ?? ''));
				$nameEsc = mysqli_real_escape_string(
					(isset($db_pbmBudget_PBM) && $db_pbmBudget_PBM instanceof mysqli) ? $db_pbmBudget_PBM : $db_Project,
					$name
				);
				$staffCode = mysqli_real_escape_string(
					(isset($db_pbmBudget_PBM) && $db_pbmBudget_PBM instanceof mysqli) ? $db_pbmBudget_PBM : $db_Project,
					(string)($_SESSION['code'] ?? ($r_staff['code'] ?? ''))
				);
				if (isset($db_pbmBudget_PBM) && $db_pbmBudget_PBM instanceof mysqli) {
					$codeSeed = substr(preg_replace('/\D+/', '', (string)microtime(true)), -6);
					if ($codeSeed === '') {
						$codeSeed = (string)mt_rand(100000, 999999);
					}
					$codeEsc = mysqli_real_escape_string($db_pbmBudget_PBM, $codeSeed);
					$sql = mysqli_query(
						$db_pbmBudget_PBM,
						"INSERT INTO budget_set_source
							(budget_source_code, budget_source_name, budget_source_status, budget_source_date_create, budget_source_staff_create)
						 VALUES ('$codeEsc', '$nameEsc', '1', NOW(), '$staffCode')"
					);
				} else {
					$sql = mysqli_query($db_Project, "INSERT INTO set_project_budget(name,status)VALUE('$nameEsc','$status')");
				}
			} elseif ($op_from == "set_project_expenses") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "INSERT INTO set_project_expenses(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_project_status") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "INSERT INTO set_project_status(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_project_target") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "INSERT INTO set_project_target(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "set_project_personnel") {
				$name = mysqli_real_escape_string($db_Project, $_POST['name']);
				$code_department = isset($_POST['code_department']) ? (int) $_POST['code_department'] : 0;
				$code_job = isset($_POST['code_job']) ? (int) $_POST['code_job'] : 0;
				$code_unit = isset($_POST['code_unit']) ? (int) $_POST['code_unit'] : 0;
				$vcd = $code_department > 0 ? "'" . (int) $code_department . "'" : 'NULL';
				$vcj = $code_job > 0 ? "'" . (int) $code_job . "'" : 'NULL';
				$vcu = $code_unit > 0 ? "'" . (int) $code_unit . "'" : 'NULL';
				$sql = mysqli_query($db_Project, "INSERT INTO set_project_personnel(name,code_department,code_job,code_unit,status) VALUE('$name',$vcd,$vcj,$vcu,'$status')");
			} elseif ($op_from == "add_unit_with_personnel") {
				$unit_name = mysqli_real_escape_string($db_Project, trim((string)($_POST['unit_name'] ?? '')));
				$code_department = isset($_POST['code_department']) ? (int) $_POST['code_department'] : 0;
				$code_job = isset($_POST['code_job']) ? (int) $_POST['code_job'] : 0;
				$staff_codes = $_POST['staff_codes'] ?? [];
				if (!is_array($staff_codes)) {
					$staff_codes = [$staff_codes];
				}
				$staff_codes = array_values(array_filter(array_map(function($v) {
					return trim((string)$v);
				}, $staff_codes), function($v) {
					return $v !== '';
				}));

				$vcd = $code_department > 0 ? "'" . (int) $code_department . "'" : 'NULL';
				$vcj = $code_job > 0 ? "'" . (int) $code_job . "'" : 'NULL';

				if ($unit_name === '') {
					$sql = false;
				} else {
					$sqlUnit = mysqli_query($db_Project, "INSERT INTO set_unit(name,status) VALUE('$unit_name','$status')");
					if ($sqlUnit) {
						$new_unit_id = (int) mysqli_insert_id($db_Project);
						$allOk = true;

						// รายชื่อบุคลากรเป็นทางเลือก — กำหนดทีหลังได้
						if (!empty($staff_codes)) {
							$codesEsc = array_map(function($c) use ($db_person) {
								return mysqli_real_escape_string($db_person, (string)$c);
							}, $staff_codes);
							$in = "'" . implode("','", $codesEsc) . "'";
							$personnelNameMap = [];
							$sqlStaff = mysqli_query(
								$db_person,
								"SELECT code, CONCAT(TRIM(fname),' ',TRIM(lname)) AS full_name
								 FROM staff
								 WHERE work_status IN ('0','1') AND code IN ($in)"
							);
							if ($sqlStaff) {
								while ($r = mysqli_fetch_assoc($sqlStaff)) {
									$personnelNameMap[(string)($r['code'] ?? '')] = trim((string)($r['full_name'] ?? ''));
								}
							}

							foreach ($staff_codes as $staff_code_raw) {
								$pName = $personnelNameMap[(string)$staff_code_raw] ?? '';
								$pName = trim((string)$pName);
								if ($pName === '') {
									continue;
								}

								$pNameEsc = mysqli_real_escape_string($db_Project, $pName);

								// กันซ้ำแบบหยาบ: ชื่อเดียวกันในหน่วยเดียวกัน (status != '99')
								$check = mysqli_query(
									$db_Project,
									"SELECT 1 FROM set_project_personnel
									 WHERE code_unit='$new_unit_id' AND name='$pNameEsc' AND status!='99'
									 LIMIT 1"
								);

								if ($check && mysqli_num_rows($check) > 0) {
									continue;
								}

								$insertPersonnel = mysqli_query(
									$db_Project,
									"INSERT INTO set_project_personnel(name,code_department,code_job,code_unit,status)
									 VALUES('$pNameEsc',$vcd,$vcj,'$new_unit_id','$status')"
								);
								if (!$insertPersonnel) {
									$allOk = false;
									break;
								}
							}
						}

						// กลับไปหน้าแสดงหน่วยงาน/รายชื่อบุคลากร
						$op_from = "set_project_personnel";
						$sql = $allOk;
					} else {
						$sql = false;
					}
				}
			} elseif ($op_from == "expenses_detail") {
				if (function_exists('ensureSetExpensesDetailAttachSchema')) {
					ensureSetExpensesDetailAttachSchema($db_Project);
				}
				$code_expenses = (int)($_POST['id'] ?? 0);
				$name = mysqli_real_escape_string($db_Project, (string)($_POST['name'] ?? ''));
				$detail = mysqli_real_escape_string($db_Project, (string)($_POST['detail'] ?? ''));
				$op_from = "set_project_expenses";
				$attachFileSql = 'NULL';
				$attachNameSql = 'NULL';
				$up = function_exists('pmSaveUploadedExpenseAttach') ? pmSaveUploadedExpenseAttach('attach_file') : ['ok' => 0, 'empty' => 1];
				if (!empty($up['ok'])) {
					$attachFileSql = "'" . mysqli_real_escape_string($db_Project, (string)$up['stored']) . "'";
					$attachNameSql = "'" . mysqli_real_escape_string($db_Project, (string)$up['name']) . "'";
					$sql = mysqli_query(
						$db_Project,
						"INSERT INTO set_project_expenses_detail(code_expenses,name,detail,attach_file,attach_name,status)
						 VALUES('$code_expenses','$name','$detail',$attachFileSql,$attachNameSql,'$status')"
					);
				} elseif (empty($up['empty'])) {
					$sql = false;
				} else {
					$sql = mysqli_query(
						$db_Project,
						"INSERT INTO set_project_expenses_detail(code_expenses,name,detail,attach_file,attach_name,status)
						 VALUES('$code_expenses','$name','$detail',NULL,NULL,'$status')"
					);
				}
			} elseif ($op_from == "set_project_consistency") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "INSERT INTO set_project_consistency(name,status)VALUE('$name','$status')");
			} elseif ($op_from == "consistency_detail") {
				$code_consistency = $_POST['id'];
				$name = $_POST['name'];
				$op_from = "set_project_consistency";
				$sql = mysqli_query($db_Project, "INSERT INTO set_project_consistency_detail(code_consistency,name,status)VALUE('$code_consistency','$name','$status')");
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
				global $db_pbmBudget_PBM;
				$name = trim((string)($_POST['name'] ?? ''));
				$id = (int)($_POST['id'] ?? 0);
				$dbBud = (isset($db_pbmBudget_PBM) && $db_pbmBudget_PBM instanceof mysqli) ? $db_pbmBudget_PBM : $db_Project;
				$nameEsc = mysqli_real_escape_string($dbBud, $name);
				$staffCode = mysqli_real_escape_string($dbBud, (string)($_SESSION['code'] ?? ($r_staff['code'] ?? '')));
				if (isset($db_pbmBudget_PBM) && $db_pbmBudget_PBM instanceof mysqli && $id > 0) {
					$sql = mysqli_query(
						$db_pbmBudget_PBM,
						"UPDATE budget_set_source
						 SET budget_source_name='$nameEsc',
						     budget_source_date_modify=NOW(),
						     budget_source_staff_modify='$staffCode'
						 WHERE budget_source_id='" . $id . "' LIMIT 1"
					);
				} else {
					$sql = mysqli_query($db_Project, "UPDATE set_project_budget SET name='$nameEsc' WHERE id='" . $id . "' ");
				}
			} elseif ($op_from == "set_project_expenses") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "UPDATE set_project_expenses SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_project_status") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "UPDATE set_project_status SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_project_target") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "UPDATE set_project_target SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "set_project_personnel") {
				$name = mysqli_real_escape_string($db_Project, $_POST['name']);
				$code_department = isset($_POST['code_department']) ? (int) $_POST['code_department'] : 0;
				$code_job = isset($_POST['code_job']) ? (int) $_POST['code_job'] : 0;
				$code_unit = isset($_POST['code_unit']) ? (int) $_POST['code_unit'] : 0;
				$pid = (int) $_POST['id'];
				$vcd = $code_department > 0 ? "'" . (int) $code_department . "'" : 'NULL';
				$vcj = $code_job > 0 ? "'" . (int) $code_job . "'" : 'NULL';
				$vcu = $code_unit > 0 ? "'" . (int) $code_unit . "'" : 'NULL';
				$sql = mysqli_query($db_Project, "UPDATE set_project_personnel SET name='$name', code_department=$vcd, code_job=$vcj, code_unit=$vcu WHERE id='$pid' ");
			} elseif ($op_from == "expenses_detail") {
				if (function_exists('ensureSetExpensesDetailAttachSchema')) {
					ensureSetExpensesDetailAttachSchema($db_Project);
				}
				$name = mysqli_real_escape_string($db_Project, (string)($_POST['name'] ?? ''));
				$detail = mysqli_real_escape_string($db_Project, (string)($_POST['detail'] ?? ''));
				$eid = (int)($_POST['expenses_id'] ?? 0);
				$op_from = "set_project_expenses";
				$setExtra = '';
				$up = function_exists('pmSaveUploadedExpenseAttach') ? pmSaveUploadedExpenseAttach('attach_file') : ['ok' => 0, 'empty' => 1];
				$oldRow = null;
				if ($eid > 0) {
					$qOld = @mysqli_query($db_Project, "SELECT attach_file FROM set_project_expenses_detail WHERE id='" . $eid . "' LIMIT 1");
					$oldRow = $qOld ? mysqli_fetch_assoc($qOld) : null;
				}
				if (!empty($up['ok'])) {
					$af = mysqli_real_escape_string($db_Project, (string)$up['stored']);
					$an = mysqli_real_escape_string($db_Project, (string)$up['name']);
					$setExtra = ", attach_file='$af', attach_name='$an'";
					$oldStored = trim((string)($oldRow['attach_file'] ?? ''));
					if ($oldStored !== '' && function_exists('pmDeleteExpenseAttachFile')) {
						pmDeleteExpenseAttachFile($oldStored);
					}
					$sql = mysqli_query($db_Project, "UPDATE set_project_expenses_detail SET name='$name',detail='$detail'$setExtra WHERE id='$eid' ");
				} elseif (empty($up['empty'])) {
					$sql = false;
				} else {
					if (!empty($_POST['remove_attach'])) {
						$oldStored = trim((string)($oldRow['attach_file'] ?? ''));
						if ($oldStored !== '' && function_exists('pmDeleteExpenseAttachFile')) {
							pmDeleteExpenseAttachFile($oldStored);
						}
						$setExtra = ", attach_file=NULL, attach_name=NULL";
					}
					$sql = mysqli_query($db_Project, "UPDATE set_project_expenses_detail SET name='$name',detail='$detail'$setExtra WHERE id='$eid' ");
				}
			} elseif ($op_from == "set_project_consistency") {
				$name = $_POST['name'];
				$sql = mysqli_query($db_Project, "UPDATE set_project_consistency SET name='$name' WHERE id='$_POST[id]' ");
			} elseif ($op_from == "consistency_detail") {
				$name = $_POST['name'];
				$op_from = "set_project_consistency";
				$sql = mysqli_query($db_Project, "UPDATE set_project_consistency_detail SET name='$name' WHERE id='$_POST[consistency_id]' ");
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
			global $db_Project, $db_person, $db_pbmBudget_PBM;
			$s_staff = mysqli_query($db_person, "select * from staff where email='$_SESSION[r_email]'  and  code='$_SESSION[code]'");
			$r_staff = mysqli_fetch_array($s_staff);
			$page = $_GET['page'];
			$op_from = $_POST['op_from'] ?? '';
			$tb_data = (string)($_POST['tb_data'] ?? '');
			$id = isset($_POST['id']) && (string)$_POST['id'] !== '' ? $_POST['id'] : ($_GET['id'] ?? '');
			if ($tb_data === 'set_project_expexnses_detail') {
				$tb_data = 'set_project_expenses_detail';
			}
			$allowedDelTables = [
				'set_project_type',
				'set_project_nature',
				'set_project_budget',
				'set_project_expenses',
				'set_project_expenses_detail',
				'set_project_status',
				'set_project_target',
				'set_project_personnel',
				'set_project_consistency',
				'set_project_consistency_detail',
				'set_unit',
				'set_department',
			];
			$sql = false;
			if ($id != "" && in_array($tb_data, $allowedDelTables, true)) {
				if ($tb_data === 'set_project_budget' && isset($db_pbmBudget_PBM) && $db_pbmBudget_PBM instanceof mysqli) {
					$idEsc = (int)$id;
					$staffCode = mysqli_real_escape_string($db_pbmBudget_PBM, (string)($_SESSION['code'] ?? ($r_staff['code'] ?? '')));
					$sql = mysqli_query(
						$db_pbmBudget_PBM,
						"UPDATE budget_set_source
						 SET budget_source_status='99',
						     budget_source_date_modify=NOW(),
						     budget_source_staff_modify='$staffCode'
						 WHERE budget_source_id='" . $idEsc . "' LIMIT 1"
					);
				} else {
					$idEsc = mysqli_real_escape_string($db_Project, (string)$id);
					$sql = mysqli_query($db_Project, "UPDATE `$tb_data` SET status='99' WHERE id='$idEsc' ");
				}
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

		default:
			if ($page === 'setting') {
				if ($op === '') {
					set_Project_Type();
				} else {
					echo '<div class="alert alert-warning mt-3 mx-3">ไม่พบเมนูสำหรับค่า op นี้ กรุณาเลือกจากเมนูด้านซ้าย</div>';
				}
			}
			break;
	}
	?>



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
	// toggle status (ไม่พึ่ง jQuery เพราะ jQuery ของ template โหลดท้ายหน้า)
	(function() {
		function showToast(message, type) {
			type = type || 'info';
			const bgColor = ({
				success: "#28a745",
				info: "#17a2b8",
				error: "#dc3545"
			})[type] || "#6c757d";

			const toast = document.createElement('div');
			toast.textContent = message;
			Object.assign(toast.style, {
				position: "fixed",
				bottom: "30px",
				right: "30px",
				background: bgColor,
				color: "#fff",
				padding: "10px 20px",
				borderRadius: "8px",
				boxShadow: "0 2px 6px rgba(0,0,0,0.3)",
				zIndex: 9999,
				fontSize: "14px",
				opacity: "0",
				transition: "opacity 200ms ease"
			});
			document.body.appendChild(toast);
			requestAnimationFrame(() => { toast.style.opacity = "1"; });
			setTimeout(() => {
				toast.style.opacity = "0";
				setTimeout(() => toast.remove(), 450);
			}, 2000);
		}

		async function postStatus(id, status, type) {
			const fd = new FormData();
			fd.append('id', String(id));
			fd.append('status', String(status));
			fd.append('type', String(type));
			const res = await fetch('update_status.php', {
				method: 'POST',
				body: fd,
				credentials: 'same-origin'
			});
			const text = (await res.text()).trim();
			return { ok: res.ok, text };
		}

		document.addEventListener('change', async function(ev) {
			const el = ev.target;
			if (!el || !el.classList || !el.classList.contains('ace-switch')) return;
			const id = el.getAttribute('data-id');
			const type = el.getAttribute('data-type');
			if (!id || !type) return;

			const nextStatus = el.checked ? 1 : 0;
			const prevChecked = !el.checked;

			el.disabled = true;
			try {
				const r = await postStatus(id, nextStatus, type);
				if (r.text === 'success') {
					showToast('บันทึกเรียบร้อยแล้ว', 'success');
				} else if (r.text === 'no_change') {
					showToast('ไม่มีการเปลี่ยนแปลง', 'info');
				} else {
					el.checked = prevChecked;
					showToast('เกิดข้อผิดพลาด: ' + r.text, 'error');
				}
			} catch (e) {
				el.checked = prevChecked;
				showToast('การเชื่อมต่อผิดพลาด', 'error');
			} finally {
				el.disabled = false;
			}
		});
	})();
</script>
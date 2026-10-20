<?php
session_start();
include("../../../config/connect_db.php");
include("../../../config/config_dates.php");
include("../../../config/main_function.php");
include("../fn/main_staff.php");
include("../fn/main_default.php");
// echo $_SESSION['code'];
// echo $_SESSION['email'];
if (isset($_GET['PharCode']) != "") {
	$PharCode = $_GET['PharCode'];
	$sql = mysqli_query($db_person, "select * from staff where code='$PharCode' and (work_status=0 or work_status=1 )  ");
	$row3 = mysqli_fetch_array($sql);
	$_SESSION['code'] = $row3['code'];
	$_SESSION['r_email'] = $row3['email'];
	$_SESSION['hash_pw'] = $row3['hash_pw'];
}



$sql = "select * from staff where code='$_SESSION[code]'  and  email='$_SESSION[r_email]' ";
$result = mysqli_query($db_person, $sql) or die("คุณไม่มีสิทธิ์เข้าใช้งานค่ะ");
if ($row = mysqli_fetch_array($result)) {

	$page = $_GET['page'];
	$op = $_GET['op'];
	if ($page == "") {
		$page = 'dashboard';
	}
	if ($page == "dashboard") {
		$page_name = 'Dashboard';
	}
	if ($page == "setting") {
		$page_name = 'ข้อมูลพื้นฐาน';
	}
	if ($page == "kpi") {
		$page_name = 'ข้อมูลตัวชี้วัด';
	}
	if ($page == "kpi_year") {
		$page_name = 'จัดการตัวชี้วัดรายปีงบประมาณ';
	}
	//echo "$page";

	$email = $row['email'];
	$email_exe = $row['email_exe'];
	$position_code = $row['position_code'];

	$r_position = getPosition($db_person, $position_code);

?>
	<!doctype html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
		<!-- <base href=".../Template/ace/" /> -->

		<title><?= $title; ?></title>

		<!-- include common vendor stylesheets & fontawesome -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap/dist/css/bootstrap.css">

		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/@fortawesome/fontawesome-free/css/fontawesome.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/@fortawesome/fontawesome-free/css/regular.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/@fortawesome/fontawesome-free/css/brands.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/@fortawesome/fontawesome-free/css/solid.css">




		<!-- include vendor stylesheets used in "Dashboard 4" page. see "/views//pages/partials/dashboard-4/@vendor-stylesheets.hbs" -->
		<!-- "Dashboard" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/dashboard-2/@page-style.css">
		<!-- "Charts" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/charts/@page-style.css">

		<!-- include vendor stylesheets used in "Horizontal Menu" page. see "/views//pages/partials/horizontal/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/fullcalendar/main.css">

		<!-- include fonts -->
		<!-- <link rel="stylesheet" type="text/css" href="../../Template/../../Template/ace/dist/css/../Template/../Template/ace-font.css"> -->
		<!-- <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" /> -->
		<link href="https://fonts.googleapis.com/css?family=Kanit:300|Mitr&display=swap" rel="stylesheet">


		<!-- include vendor stylesheets used in "Basic Elements" page. see "/views//pages/partials/form-basic/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/nouislider/distribute/nouislider.min.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/ion-rangeslider/css/ion.rangeSlider.min.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/tiny-date-picker/tiny-date-picker.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/tiny-date-picker/date-range-picker.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.css">


		<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
		<script src="https://unpkg.com/gijgo@1.9.13/js/gijgo.min.js" type="text/javascript"></script>
		<link href="https://unpkg.com/gijgo@1.9.13/css/gijgo.min.css" rel="stylesheet" type="text/css" />


		<!-- include vendor stylesheets used in "Calendar" page. see "/views//pages/partials/calendar/@vendor-stylesheets.hbs" -->
		<!-- <link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/fullcalendar/main.css"> -->

		<!-- ../Template/ace.css -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/dist/css/ace.css">

		<!-- favicon -->
		<link rel="icon" type="image/png" href="../../Template/ace/assets/mis.jpg" />

		<!-- include vendor stylesheets used in "Charts" page. see "/views//pages/partials/charts/@vendor-stylesheets.hbs" -->
		<!-- <link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/chartist/dist/chartist.min.css"> -->

		<!-- include vendor stylesheets used in "Icons" page. see "/views//pages/partials/icons/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/jam-icons/css/jam.min.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/eva-icons/style/eva-icons.css">

		<!-- "Profile" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/page-profile/@page-style.css">

		<!-- include vendor stylesheets used in "Buttons" page. see "/views//pages/partials/buttons/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/node-waves/dist/waves.css">


		<!-- include vendor stylesheets used in "Gallery" page. see "/views//pages/partials/gallery/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/photoswipe/dist/photoswipe.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/photoswipe/dist/default-skin/default-skin.css">

		<!-- include vendor stylesheets used in "DataTables" page. see "/views//pages/partials/table-datatables/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/datatables.net-bs4/css/dataTables.bootstrap4.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/datatables.net-buttons-bs4/css/buttons.bootstrap4.css">

		<!-- "DataTables" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/table-datatables/@page-style.css">

		<!-- include vendor stylesheets used in "Bootstrap Table" page. see "/views//pages/partials/table-bootstrap/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap-table/dist/bootstrap-table.css">


		<!-- include vendor stylesheets used in "More Elements" page. see "/views//pages/partials/form-more/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap-star-rating/css/star-rating.css">

		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap-select/dist/css/bootstrap-select.css">

		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap-duallistbox/dist/bootstrap-duallistbox.css">


		<!-- <link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/select2/dist/css/select2.css"> -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/chosen-js/chosen.css">



		<!-- "Horizontal Menu" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/dashboard-2/@page-style.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/dist/css/ace-themes.css">


		<!-- "Dashboard 4" page styles, specific to this page for demo only -->
		<!-- <link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/dashboard-4/@page-style.css"> -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/dist/css/ace-themes.css">

		<!-- include vendor stylesheets used in "Wysiwyg & Markdown" page. see "/views//pages/partials/form-wysiwyg/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/summernote/dist/summernote-lite.css">

		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/bootstrap-markdown/css/bootstrap-markdown.min.css">
		<link rel="stylesheet" type="text/css" href="../../Template/ace/views/pages/form-wysiwyg/@page-style.css">

		<!-- include vendor stylesheets used in "File Upload" page. see "/views//pages/partials/form-upload/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../../Template/ace/node_modules/dropzone/dist/dropzone.css">

		<!-- ckeditor -->
		<script type="text/javascript" src="ckeditor2/ckeditor.js"></script>
		<script type="text/javascript" src="../../vendor-cmu/sweetalert.min.js"></script>

		<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
		<!-- <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script> -->
		<!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->
		<!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
		<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script> -->

		<style>
			/* สี background + ตัวหนังสือของรายการที่เลือก */
			.select2-container--default .select2-selection--multiple .select2-selection__choice {
				background-color: #5fa1e6ff !important;
				/* สีน้ำเงิน Bootstrap */
				border: 1px solid #569ce7ff !important;
				color: #fff !important;
				display: block !important;
				width: 100%;
				margin: 2px 0 !important;

				border-radius: 4px !important;
				padding: 5px 10px !important;
			}

			/* ปุ่มกากบาทของ tag ที่เลือก */
			.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
				color: #d41c1c !important;
				/* กากบาทสีแดง */


				border-radius: 50%;
				/* ทำให้เป็นวงกลม */
				width: 20px;
				/* ขนาดวงกลม */
				height: 20px;
				display: inline-flex;
				/* จัดให้อยู่กลาง */
				align-items: center;
				justify-content: center;
				font-size: 22px;
				/* ขนาดกากบาท */
				font-weight: bold;
				margin-right: 6px;
				/* ระยะห่างจากข้อความ */
				cursor: pointer;
			}

			/* เอฟเฟกต์เวลา hover */
			.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
				background-color: #d41c1c !important;
				/* วงกลมแดง */
				color: #fff !important;
				/* กากบาทขาว */
				transform: scale(1.2);
				/* ขยายเล็กน้อย */
			}
		</style>

	</head>

	<body style="font-family: 'Kanit', sans-serif;">
		<div class="body-container">

			<div class="main-container">

				<div id="sidebar" class="sidebar sidebar-dark sidebar-color sidebar-fixed sidebar-backdrop expandable"
					data-swipe="true" data-dismiss="true">
					<div class="sidebar-inner">

						<div class="ace-scroll flex-grow-1 mt-1px" data-ace-scroll="{}">
							<!-- all sidebar header is inside scrollable area -->
							<!-- .navbar-brand inside sidebar, only shown in desktop view -->
							<div class="d-none d-xl-flex sidebar-section-item fadeable-left fadeable-top">
								<div class="fadeinable">
									<!-- shown when sidebar is collapsed -->
									<div class="py-2 mx-1" id="sidebar-header-brand1">
										<a class="navbar-brand text-140">
											<i class="fa fa-leaf text-success-l1"></i>
										</a>
									</div>
								</div>

								<div class="fadeable w-100">
									<!-- shown when sidebar is full-width -->
									<div class="py-2 text-center mx-3" id="sidebar-header-brand2">
										<a class="navbar-brand ml-n2 text-100 text-white" href="#">
											<!-- <i class="fa fa-leaf mr-1 text-success-l1"></i> -->
											<div class="text-orange text-180">KPI</div> <span>PHARMACY CMU</span>
										</a>
									</div>
								</div>
							</div><!-- /.sidebar-section-item  -->


							<!-- the user avatar and image -->
							<div class="sidebar-section-item pt-2 fadeable-left fadeable-top">
								<div class="fadeinable">
									<img src="<?= $path_picture; ?>/<?= $row['photo']; ?>" width="48"
										class="p-2px border-2 brc-primary-tp2 radius-round" />
								</div>

								<div class="fadeable hideable">
									<div class="py-2 d-flex flex-column align-items-center">
										<img src="<?= $path_picture; ?>/<?= $row['photo']; ?>" width="100"
											class="p-2px border-2 brc-primary-m2 radius-round" />

										<div class="text-center mt-1" id="id-user-info">
											<a href="#id-user-menu"
												class="d-style pos-rel collapsed text-orange-l2 accordion-toggle no-underline bgc-h-white-tp9 radius-1 px-1 py-2px"
												data-toggle="collapse" aria-expanded="false">
												<span class="text-95 font-bolder"><?= $row['fname'] ?>
													<?= $row['lname'] ?></span>
												<i class="fa fa-caret-down text-90 d-collapsed"></i>
												<i class="fa fa-caret-up text-90 d-n-collapsed"></i>
											</a>
											<div class="text-white text-85"><?= $r_position['name']; ?></div>
										</div><!-- /#id-user-info -->

										<div class="collapse" id="id-user-menu">
											<div class="mt-3">
												<a href="#" class="btn bgc-black-tp8 btn-h-outline-blue py-2 px-1">
													<i class="fa fa-envelope w-4 text-110 text-blue-m2"></i>
												</a>

												<a href="#" class="btn bgc-black-tp8 btn-h-outline-purple py-2 px-1">
													<i class="fa fa-users w-4 text-110 text-purple-m2"></i>
												</a>

												<a href="#" class="btn bgc-black-tp8 btn-h-outline-orange py-2 px-1">
													<i class="fa fa-star w-4 text-110 text-orange-m2"></i>
												</a>
											</div>
										</div><!-- /.collapse -->
									</div>
								</div>
							</div>

							<!-- optional `nav` tag -->
							<nav aria-label="Main">
								<ul class="nav flex-column mt-2 has-active-border active-on-top">


									<li class="nav-item-caption">
										<span class="fadeable pl-3">ADMIN</span>
										<span class="fadeinable mt-n2 text-125">&hellip;</span>

									</li>


									<li class="nav-item <?php if ($page == 'dashboard') {
															echo "active";
														} ?>">

										<a href="html/dashboard.html" class="nav-link">
											<i class="nav-icon fa fa-tachometer-alt"></i>
											<span class="nav-text fadeable">
												<span>Dashboard</span>
											</span>


										</a>

										<b class="sub-arrow"></b>

									</li>


									<li class="nav-item  <?php if ($page == 'setting') {
																echo "active open";
															} ?> ">

										<a href="#" class="nav-link dropdown-toggle">
											<i class="nav-icon fa fa-cube"></i>
											<span class="nav-text fadeable">
												<span>ข้อมูลพื้นฐาน</span>
											</span>

											<b class="caret fa fa-angle-left rt-n90"></b>


										</a>

										<div class="hideable submenu collapse <?php if ($page == 'setting') {
																					echo "show";
																				} ?>">
											<ul class="submenu-inner">

												<li class="nav-item <?php if (($page == 'setting') && ($op == 'set_mark')) {
																		echo "active";
																	} ?>">

													<a href="?page=setting&op=set_mark" class="nav-link">

														<span class="nav-text">
															<span>กำหนดเครื่องหมาย</span>
														</span>


													</a>


												</li>


												<li class="nav-item  <?php if (($page == 'setting') && ($op == 'set_unit')) {
																			echo "active";
																		} ?>">

													<a href="?page=setting&op=set_unit" class="nav-link">

														<span class="nav-text">
															<span>กำหนดหน่วยนับ</span>
														</span>


													</a>


												</li>


												<li class="nav-item <?php if (($page == 'setting') && ($op == 'set_count')) {
																		echo "active";
																	} ?>">

													<a href="?page=setting&op=set_count" class="nav-link">

														<span class="nav-text">
															<span>กำหนดการนับ</span>
														</span>


													</a>


												</li>


												<li class="nav-item <?php if (($page == 'setting') && ($op == 'set_report_round')) {
																		echo "active";
																	} ?>">

													<a href="?page=setting&op=set_report_round" class="nav-link">

														<span class="nav-text">
															<span>กำหนดรอบการรายงาน</span>
														</span>


													</a>


												</li>


												<li class="nav-item  <?php if (($page == 'setting') && ($op == 'set_type')) {
																			echo "active";
																		} ?>">

													<a href="?page=setting&op=set_type" class="nav-link">

														<span class="nav-text">
															<span>กำหนดประเภทตัวชี้วัด</span>
														</span>


													</a>


												</li>


												<li class="nav-item  <?php if (($page == 'setting') && ($op == 'set_so')) {
																			echo "active";
																		} ?>">

													<a href="?page=setting&op=set_so" class="nav-link">

														<span class="nav-text">
															<span>กำหนด SO</span>
														</span>


													</a>


												</li>


												<li class="nav-item  <?php if (($page == 'setting') && ($op == 'set_side')) {
																			echo "active";
																		} ?>">

													<a href="?page=setting&op=set_side" class="nav-link">

														<span class="nav-text">
															<span>กำหนดด้าน</span>
														</span>


													</a>


												</li>
												<li class="nav-item  <?php if (($page == 'setting') && ($op == 'set_ap')) {
																			echo "active";
																		} ?>">

													<a href="?page=setting&op=set_ap" class="nav-link">

														<span class="nav-text">
															<span>กำหนดแผนปฏิบัติการ (AP)</span>
														</span>


													</a>


												</li>


												<li class="nav-item  <?php if (($page == 'setting') && ($op == 'set_tag')) {
																			echo "active";
																		} ?>">

													<a href="?page=setting&op=set_tag" class="nav-link">

														<span class="nav-text">
															<span>กำหนด Tag</span>
														</span>


													</a>


												</li>
												<li class="nav-item <?php if (($page == 'setting') && ($op == 'set_owner')) {
																		echo "active";
																	} ?>">

													<a href="?page=setting&op=set_owner" class="nav-link">

														<span class="nav-text">
															<span>กำหนด data owner/kpi owner</span>
														</span>


													</a>


												</li>

											</ul>
										</div>

										<b class="sub-arrow"></b>

									</li>


									<li class="nav-item <?php if ($page == 'kpi') {
															echo "active open";
														} ?>">

										<a href="#" class="nav-link dropdown-toggle collapsed">
											<i class="nav-icon fa fa-desktop"></i>
											<span class="nav-text fadeable">
												<span>ข้อมูลตัวชี้วัด</span>
											</span>

											<b class="caret fa fa-angle-left rt-n90"></b>

											<!-- or you can use custom icons. first add `d-style` to 'A' -->
											<!--
					 <b class="caret d-n-collapsed fa fa-minus text-80"></b>
					 <b class="caret d-collapsed fa fa-plus text-80"></b>
				 -->
										</a>

										<div class="hideable submenu collapse <?php if ($page == 'kpi') {
																					echo "show";
																				} ?> ">
											<ul class="submenu-inner">

												<li class="nav-item <?php if (($page == 'kpi') && ($op == 'kpi')) {
																		echo "active";
																	} ?>">

													<a href="?page=kpi&op=kpi" class="nav-link">

														<span class="nav-text">
															<span>ฐานข้อมูลตัวชี้วัด+นิยาม</span>
														</span>


													</a>


												</li>

											</ul>
										</div>

										<b class="sub-arrow"></b>

									</li>


									<li class="nav-item <?php if ($page == 'kpi_year') {
															echo "active open";
														} ?>">

										<a href="#" class="nav-link dropdown-toggle collapsed">
											<i class="nav-icon fa fa-table"></i>
											<span class="nav-text fadeable">
												<span>จัดการตัวชี้วัดรายปีงบ</span>
											</span>

											<b class="caret fa fa-angle-left rt-n90"></b>


										</a>

										<div class="hideable submenu collapse <?php if ($page == 'kpi_year') {
																					echo "show";
																				} ?>">
											<ul class="submenu-inner">
												<?php
												$years_select = $_GET['years_select'];
												$year_list = getYearSelect($db_pbmBudget);
												foreach ($year_list as $year) {

												?>
													<li class="nav-item <?php if (($page == 'kpi_year') && ($year['budget_year_name'] == $years_select)) {
																			echo "active";
																		} ?>">

														<a href="?page=kpi_year&op=kpi&years_select=<?= $year['budget_year_name']; ?>" class="nav-link">

															<span class="nav-text">
																<span><?php echo htmlspecialchars($year['budget_year_name']); ?></span>
															</span>


														</a>


													</li>
												<?php } ?>


											</ul>
										</div>

										<b class="sub-arrow"></b>

									</li>

								</ul>
							</nav>

						</div><!-- /.ace-scroll -->


						<div class="sidebar-section">
							<div class="sidebar-section-item fadeable-left fadeable-top">
								<div class="fadeinable">
									<button type="button" class="btn btn-primary btn-bold">
										<i class="fa fa-cloud"></i>
									</button>
								</div>

								<div id="sidebar-footer" class="fadeable text-center border-t-1 brc-white-tp10 w-95">
									<div class="py-2">
										<a href="../logout.php" class="d-style pos-rel no-underline"
											aria-expanded="false">
											<button type="button" class="btn btn-danger px-5 btn-bold">
												ออกจากระบบ
											</button>
										</a>
									</div>
								</div>
							</div>
						</div>

					</div>
				</div>

				<div role="main" class="main-content">
					<nav class="navbar navbar-sm navbar-expand-lg navbar-fixed navbar-white">
						<div class="navbar-inner shadow-md">

							<button type="button"
								class="btn btn-burger align-self-center ml-25 mr-2 d-none d-xl-flex btn-h-lighter-blue"
								data-toggle="sidebar" data-target="#sidebar" aria-controls="sidebar" aria-expanded="true"
								aria-label="Toggle sidebar">
								<span class="bars text-default"></span>
							</button><!-- sidebar toggler button -->


							<div class="d-flex h-100 justify-content-xl-between align-items-center">
								<button type="button"
									class="btn btn-burger burger-arrowed static collapsed ml-2 d-flex d-xl-none btn-h-lighter-blue"
									data-toggle-mobile="sidebar" data-target="#sidebar" aria-controls="sidebar" aria-expanded="false"
									aria-label="Toggle sidebar">
									<span class="bars text-default"></span>
								</button><!-- mobile sidebar toggler button -->



								<!-- the small Dashboard selection menu -->
								<div class="d-inline-flex">
									<h2 class="d-none d-xl-inline-block text-140 text-dark-m2 mb-0 pb-1"><?= $page_name; ?></h2>


								</div>
							</div>




							<!-- search box for MOBILE view only -->
							<div class="d-lg-none navbar-content flex-grow-0 ml-auto">
								<button class="navbar-toggler h-75 px-2 px-sm-2" type="button" data-toggle="collapse"
									data-target="#navbarSearch" aria-controls="navbarSearch" aria-expanded="false"
									aria-label="Toggle navbar search">
									<i class="fa fa-search text-blue-m1 text-90"></i>
								</button>

								<div class="collapse navbar-collapse navbar-backdrop" id="navbarSearch">
									<div>
										<!-- by wrapping input element inside a "FORM[data-submit=dismiss]", when you submit the form, dropdown will be hidden -->
										<form class="d-flex align-items-center pl-4" data-submit="dismiss">
											<i class="fa fa-search text-blue-m1 mr-n425"></i>
											<input type="text" class="px-2 mx-0 w-100 autofocus border-1 py-35 pr-45 radius-round"
												placeholder="Start searching ..." aria-label="Search" />
											<a href="#"
												class="btn border-0 ml-n475 radius-100 py-15 btn-text-lightgrey btn-h-light-blue px-25 pos-rel">
												<i class="fa fa-microphone"></i>
											</a>
										</form>
									</div>
								</div>
							</div>




							<!-- #navbarMenu2 toggler -->
							<button class="navbar-toggler mx-1 p-2 px-sm-3" type="button" data-toggle="collapse"
								data-target="#navbarMenu2" aria-controls="navbarMenu2" aria-expanded="false"
								aria-label="Toggle navbar menu 2">
								<i class="fa fa-ellipsis-v text-secondary-m2"></i>
							</button>







							<!-- #navbarMenu toggler -->
							<button class="navbar-toggler ml-1 mr-2 px-1" type="button" data-toggle="collapse" data-target="#navbarMenu"
								aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navbar menu 1">
								<img class="radius-round border-2 brc-primary-tp3 p-1px" src="assets/image/avatar/avatar3.jpg" width="36"
									alt="Natalie's Photo">
							</button>


							<div class="ml-auto mr-xl-3 navbar-menu collapse navbar-collapse navbar-backdrop" id="navbarMenu">

								<div class="navbar-nav">
									<ul class="nav has-active-border">

										<li class="nav-item dropdown dropdown-mega">
											<a class="nav-link dropdown-toggle pl-lg-3 pr-lg-4" data-toggle="dropdown" href="#" role="button"
												aria-haspopup="true" aria-expanded="false">
												<i class="fa fa-bell text-110 icon-animated-bell mr-lg-2"></i>
												<span class="d-inline-block d-lg-none ml-2">Notifications</span>
												<span id="id-navbar-badge1" class="badge badge-info radius-round text-75">3</span>

												<i class="caret fa fa-angle-left d-block d-lg-none"></i>
											</a>

											<div
												class="shadow dropdown-menu dropdown-animated dropdown-sm p-0 brc-primary-m3 border-b-2 bgc-white">
												<ul
													class="nav nav-tabs nav-tabs-simple w-100 nav-justified dropdown-clickable border-b-1 brc-secondary-l2"
													role="tablist">
													<li class="nav-item">
														<a class="d-style px-0 mx-0 py-3 nav-link active text-600 brc-blue-m1 text-dark-tp5 bgc-h-blue-l4"
															data-toggle="tab" href="#navbar-notif-tab-1" role="tab">
															<span class="d-active text-blue-d1 text-105">Notifications</span>
															<span class="d-n-active">Notifications</span>
														</a>
													</li>
													<li class="nav-item">
														<a class="d-style px-0 mx-0 py-3 nav-link text-600 brc-purple-m1 text-dark-tp5 bgc-h-purple-l4"
															data-toggle="tab" href="#navbar-notif-tab-2" role="tab">
															<span class="d-active text-purple-d1 text-105">Messages</span>
															<span class="d-n-active">Messages</span>
														</a>
													</li>
												</ul><!-- .nav-tabs -->


												<div class="tab-content tab-sliding p-0">

													<div class="tab-pane mh-none show active px-md-1 pt-1" id="navbar-notif-tab-1" role="tabpanel">

														<a href="#"
															class="mb-0 border-0 list-group-item list-group-item-action btn-h-lighter-secondary">
															<i class="fab fa-twitter bgc-blue-tp1 text-white text-110 mr-15 p-2 radius-1"></i>
															<span class="text-muted">Followers</span>
															<span class="float-right badge badge-danger radius-round text-80">- 4</span>
														</a>
														<a href="#"
															class="mb-0 border-0 list-group-item list-group-item-action btn-h-lighter-secondary">
															<i class="fa fa-comment bgc-pink-tp1 text-white text-110 mr-15 p-2 radius-1"></i>
															<span class="text-muted">New Comments</span>
															<span class="float-right badge badge-info radius-round text-80">+12</span>
														</a>
														<a href="#"
															class="mb-0 border-0 list-group-item list-group-item-action btn-h-lighter-secondary">
															<i class="fa fa-shopping-cart bgc-success-tp1 text-white text-110 mr-15 p-2 radius-1"></i>
															<span class="text-muted">New Orders</span>
															<span class="float-right badge badge-success radius-round text-80">+8</span>
														</a>
														<a href="#"
															class="mb-0 border-0 list-group-item list-group-item-action btn-h-lighter-secondary">
															<i class="far fa-clock bgc-purple-tp1 text-white text-110 mr-15 p-2 radius-1"></i>
															<span class="text-muted">Finished processing data!</span>
														</a>

														<hr class="mt-1 mb-1px brc-secondary-l2" />
														<a href="#"
															class="mb-0 py-3 border-0 list-group-item text-blue text-uppercase text-center text-85 font-bolder">
															See All Notifications
															<i class="ml-2 fa fa-arrow-right text-muted"></i>
														</a>

													</div><!-- .tab-pane : notifications -->


													<div class="tab-pane mh-none pl-md-2" id="navbar-notif-tab-2" role="tabpanel">
														<div data-ace-scroll='{"ignore": "mobile", "height": 300, "smooth":true}'>
															<a href="#"
																class="d-flex mb-0 border-0 list-group-item list-group-item-action btn-h-lighter-secondary">
																<img alt="Alex's avatar" src="assets/image/avatar/avatar.png" width="48"
																	class="align-self-start border-2 brc-primary-m3 p-1px mr-2 radius-round" />
																<div>
																	<span class="text-primary-m1 font-bolder">Alex:</span>
																	<span class="text-grey text-90">Ciao sociis natoque penatibus et auctor ...</span>
																	<br />
																	<span class="text-grey-m1 text-85">
																		<i class="far fa-clock"></i>
																		a moment ago
																	</span>
																</div>
															</a>
															<hr class="my-1px brc-grey-l3" />
															<a href="#"
																class="d-flex mb-0 border-0 list-group-item list-group-item-action btn-h-lighter-secondary">
																<img alt="Susan's avatar" src="assets/image/avatar/avatar3.png" width="48"
																	class="align-self-start border-2 brc-primary-m3 p-1px mr-2 radius-round" />
																<div>
																	<span class="text-primary-m1 font-bolder">Susan:</span>
																	<span class="text-grey text-90">Vestibulum id ligula porta felis euismod ...</span>
																	<br />
																	<span class="text-grey-m1 text-85">
																		<i class="far fa-clock"></i>
																		20 minutes ago
																	</span>
																</div>
															</a>
															<hr class="my-1px brc-grey-l3" />
															<a href="#"
																class="d-flex mb-0 border-0 list-group-item list-group-item-action btn-h-lighter-secondary">
																<img alt="Bob's avatar" src="assets/image/avatar/avatar4.png" width="48"
																	class="align-self-start border-2 brc-primary-m3 p-1px mr-2 radius-round" />
																<div>
																	<span class="text-primary-m1 font-bolder">Bob:</span>
																	<span class="text-grey text-90">Nullam quis risus eget urna mollis ornare ...</span>
																	<br />
																	<span class="text-grey-m1 text-85">
																		<i class="far fa-clock"></i>
																		3:15 pm
																	</span>
																</div>
															</a>
															<hr class="my-1px brc-grey-l3" />
															<a href="#"
																class="d-flex mb-0 border-0 list-group-item list-group-item-action btn-h-lighter-secondary">
																<img alt="Kate's avatar" src="assets/image/avatar/avatar2.png" width="48"
																	class="align-self-start border-2 brc-primary-m3 p-1px mr-2 radius-round" />
																<div>
																	<span class="text-primary-m1 font-bolder">Kate:</span>
																	<span class="text-grey text-90">Ciao sociis natoque eget urna mollis ornare ...</span>
																	<br />
																	<span class="text-grey-m1 text-85">
																		<i class="far fa-clock"></i>
																		1:33 pm
																	</span>
																</div>
															</a>
															<hr class="my-1px brc-grey-l3" />
															<a href="#"
																class="d-flex mb-0 border-0 list-group-item list-group-item-action btn-h-lighter-secondary">
																<img alt="Fred's avatar" src="assets/image/avatar/avatar5.png" width="48"
																	class="align-self-start border-2 brc-primary-m3 p-1px mr-2 radius-round" />
																<div>
																	<span class="text-primary-m1 font-bolder">Fred:</span>
																	<span class="text-grey text-90">Vestibulum id penatibus et auctor ...</span>
																	<br />
																	<span class="text-grey-m1 text-85">
																		<i class="far fa-clock"></i>
																		10:09 am
																	</span>
																</div>
															</a>

														</div><!-- ace-scroll -->

														<hr class="my-1px brc-secondary-l2 border-double" />
														<a href="html/page-inbox.html"
															class="mb-0 py-3 border-0 list-group-item text-purple text-uppercase text-center text-85 font-bolder">
															See All Messages
															<i class="ml-2 fa fa-arrow-right text-muted"></i>
														</a>
													</div><!-- .tab-pane : messages -->

												</div>
											</div>
										</li>




									</ul>
								</div>

							</div>


						</div>
					</nav>
					<div class="page-content container container-plus">
						<!-- page header -->
						<div class="d-lg-none page-header py-0">
							<h1 class="text-dark-m3 pb-0 mb-0 text-125">
								Dashboard
							</h1>

							<!-- page tools -->
							<div class="page-tools d-none">
								<div class="action-buttons text-nowrap">
									<a class="btn bgc-white btn-light-secondary mx-0" href="#" data-toggle="tooltip" title="Details">
										<i class="fa fa-search-plus text-blue"></i>
									</a>
									<a class="btn bgc-white btn-light-secondary mx-0" href="#" data-toggle="tooltip" title="Print">
										<i class="fa fa-print text-purple"></i>
									</a>
									<a class="btn bgc-white btn-light-secondary mx-0" href="#" data-toggle="tooltip" title="Remove">
										<i class="fa fa-trash-alt text-danger"></i>
									</a>
								</div>
							</div>
						</div>

						<?php
						include($page . ".php");
						?>


					</div>

					<!-- this footer is shown in device width above `sm` -->
					<footer class="footer d-none d-sm-block">
						<div class="footer-inner">
							<div class="h-100 pt-3 border-t-1 brc-secondary-l2 bgc-white-tp1 shadow">
								<span class="text-primary-m1 font-bolder text-120">PHARMACY CMU</span>
								<span class="text-secondary-d2">Application &copy; 2025</span>


							</div>
						</div><!-- .footer-inner -->
					</footer>


					<!-- scroll to top button -->
					<div class="footer-tools mb-5 mb-sm-0">
						<a href="#"
							class="btn-scroll-up btn btn-white brc-black-tp6 px-25 py-2 text-95 border-2 radius-round mb-25 mr-2">
							<i class="fa fa-angle-double-up w-2 h-2"></i>
						</a>
					</div>


					<!-- this footer is shown in mobile devices below `sm` -->
					<footer class="d-sm-none footer footer-sm footer-fixed">
						<div class="footer-inner">
							<div
								class="btn-group d-flex justify-content-between h-100 px-2 py-2px bgc-white shadow-lg border-t-1 brc-secondary-l2">

								<button
									class="d-style btn btn-white btn-h-light-primary btn-a-outline-primary btn-a-bold active border-0 radius-1 px-25 mx-1">
									<span class="pos-rel">
										<i class="fa fa-home text-120 opacity-1 mr-2px"></i>
										Home
										<span class="d-active position-tl mt-n3 w-100 border-t-3 radius-2 brc-primary-m1"></span>
									</span>
								</button>

								<button class="btn btn-outline-lightgrey btn-h-light-primary btn-a-lighter-primary border-0 radius-1"
									data-toggle="modal" data-target="#id-ace-settings-modal">
									<i class="fa fa-cog text-120"></i>
								</button>

								<button data-toggle="collapse" data-target="#navbarSearch" aria-controls="navbarSearch"
									aria-expanded="false" aria-label="Toggle navbar search"
									class="btn btn-outline-lightgrey btn-h-light-primary btn-a-lighter-primary border-0 radius-1">
									<i class="fa fa-search text-120"></i>
								</button>

								<button class="btn btn-outline-lightgrey btn-h-light-primary btn-a-lighter-primary border-0 radius-1">
									<span class="pos-rel">
										<i class="fa fa-bell text-120"></i>
										<span class="badge badge-dot bgc-red position-tr mt-n1 mr-n2px"></span>
									</span>
								</button>

							</div>
						</div>
					</footer>
				</div>


			</div>


			<form autocomplete="off" class="mt-475" hidden>

				<div class="row mt-475">
					<!-- Custom file input -->
					<div class="col-12 col-md-4 mt-3 mt-md-0">
						<div class="card h-100">
							<div class="card-header">
								<h5 class="card-title text-orange-d4">
									Custom File Input
								</h5>
							</div>

							<div class="card-body">
								<div>
									<input type="file" class="ace-file-input" id="ace-file-input1" />
								</div>
								<div>
									<input type="file" class="ace-file-input" id="ace-file-input" />
								</div>

								<div class="mt-4">
									<input type="file" class="ace-file-input" id="ace-file-input2" multiple />
								</div>
							</div>
						</div>
					</div><!-- /.col -->


					<!-- Range sliders -->
					<div class="col-12 col-md-4 mt-3 mt-md-0">
						<div class="card h-100">
							<div class="card-header">
								<h5 class="card-title text-blue-d2">
									Range Sliders
								</h5>
							</div>

							<div class="card-body">
								<div class="row">
									<div class="col-2">
										<!-- vertical slider -->
										<div id="id-slider-1" class="noUi-toggle-tooltip slider-sm slider-tooltip-right slider-tooltip-caret">
										</div>
									</div>

									<div class="col-10">
										<div id="id-slider-2" class="noUi-toggle-tooltip mb-5 slider-thin"></div>

										<div id="id-slider-3" class="noUi-toggle-tooltip my-5"></div>

										<div id="id-slider-4" class="noUi-toggle-tooltip slider-sm my-5"></div>

										<div id="id-slider-5" class="noUi-toggle-tooltip my-5 slider-thin"></div>

										<div class="mt-5 mb-2">
											<!-- ionSlider -->
											<input type="text" id="id-ionslider-1" name="slider-1" value="" />
										</div>
									</div>
								</div>

							</div>
						</div>
					</div><!-- /.col -->


					<!-- Input spinner -->
					<div class="col-12 col-md-4 mt-3 mt-md-0">
						<div class="card h-100">
							<div class="card-header">
								<h5 class="card-title text-purple-d3">
									Input Spinner
								</h5>
							</div>

							<div class="card-body">
								<input class="form-control form-control-lg" id="id-spinner-1" type="text" value="55" name="id-spinner-1">

								<hr />

								<input class="form-control btn-group-inside-spinner form-control-lg" id="id-spinner-2" type="text" value="" name="id-spinner-2">

								<hr />

								<div class="w-50">
									<input class="form-control text-center" id="id-spinner-3" type="text" value="" name="id-spinner-3">
								</div>
							</div>
						</div>
					</div><!-- /.col -->
				</div><!-- /.row -->
				<div class="row mt-475">
					<!-- Date picker -->
					<div class="col-12 col-md-4 mt-3 mt-md-0">
						<div class="card h-100">
							<div class="card-header">
								<span class="text-primary-d2 text-120">Date/Range Picker</span>
							</div>

							<div class="card-body">
								<!-- Tinydate picker plugin -->
								<div class="form-field">
									<div class="pos-rel">
										<input type="text" class="form-control" id="id-date-1" />

										<input placeholder="modal mode..." type="text" class="form-control mt-25" id="id-date-2" />

										<input type="text" class="form-control mt-25" id="id-date-3" placeholder="Native datepicker on small devices ..." />
									</div>
								</div>


								<hr class="border-dotted my-35" />


								<div id="id-daterange-wrapper" class="pos-rel">
									<!-- daterange -->
									<div class="form-row">
										<div class="col">
											<input id="id-daterange-from" class="form-control ex-inputs-start" placeholder="From date" />
										</div>

										<div class="text-grey-l2">_</div>

										<div class="col">
											<input id="id-daterange-to" class="form-control ex-inputs-end" placeholder="To date" />
										</div>
									</div>

									<div id="id-daterange-container" class="dp-daterange-picker dp-daterange-above">
									</div>
								</div>


								<hr class="border-dotted my-35" />

								<!-- Datetime picker plugin -->
								<div>
									<div class="form-group">
										<label for="id_time_el" class="text-secondary-d3">Date/Time picker:</label>
										<div class="input-group date" id="id-timepicker">
											<input type="text" name="end_time" value="05:12:00" class="form-control" id="id_time_el" />
											<div class="input-group-addon input-group-append">
												<div class="input-group-text">
													<i class="far fa-clock"></i>
												</div>
											</div>
										</div>
									</div>
								</div>


								<!-- Custom time picker -->
								<div class="mt-3">
									<div class="form-group">
										<a href="#" class="btn btn-outline-info border-2" id="id-popover-timepicker">Time picker inside popover</a>

										<div id="id-popover-timepicker-html" class="d-none">
											<!-- it's a hidden element, and the html content of it will be shown inside popover -->
											<div class="d-flex">
												<select class="mx-1 pl-3 no-border caret-double ace-select border-2 radius-1 brc-default-l1 brc-h-default-m2 bgc-default-l4 text-secondary-d2 text-600 text-110" style="width: 5rem;">
													<option selected>H</option>
													<option>1</option>
													<option>2</option>
													<option>3</option>
													<option>4</option>
													<option>5</option>
													<option>6</option>
													<option>7</option>
													<option>8</option>
													<option>9</option>
													<option>10</option>
													<option>11</option>
													<option>12</option>
												</select>

												<span class="text-600 text-150">:</span>

												<select class="mx-1 pl-3 no-border caret-double ace-select border-2 radius-1 brc-default-l1 brc-h-default-m2 bgc-default-l4 text-secondary-d2 text-600 text-110" style="width: 5rem;">
													<option selected>M</option>
													<option>00</option>
													<option>05</option>
													<option>10</option>
													<option>15</option>
													<option>20</option>
													<option>25</option>
													<option>30</option>
													<option>35</option>
													<option>40</option>
													<option>45</option>
													<option>50</option>
													<option>55</option>
												</select>

												<div class="mx-1 btn-group btn-group-toggle" data-toggle="buttons">
													<div role="button" class="btn btn-bold radius-1 px-25 py-2 text-95 btn-light-blue btn-a-light-primary d-style">
														<input type="checkbox" value="1" />
														<span class="d-n-active">AM</span>
														<span class="d-active">PM</span>
													</div>
												</div>
											</div>
										</div>
									</div><!-- /.form-group -->
								</div>

							</div><!-- /.card-body -->
						</div><!-- /.card -->
					</div><!-- /.col -->


					<!-- Color picker -->
					<div class="col-12 col-md-4 mt-3 mt-md-0">
						<div class="card h-100">
							<div class="card-header">
								<span class="text-primary-d2 text-120">
									Color Picker
								</span>
							</div>

							<div class="card-body">
								<div class="input-group">
									<div class="input-group-prepend">
										<span class="input-group-text border-0 radius-0" id="id-color-picker-1-update"></span>
									</div>
									<input type="text" class="form-control" id="id-color-picker-1" />
								</div>

								<hr class="my-4" />

								<div>
									<div id="color-picker-container"></div>
								</div>
							</div>
						</div>
					</div><!-- /.col -->


					<!-- knob -->
					<div class="col-12 col-md-4 mt-3 mt-md-0">
						<div class="card h-100">
							<div class="card-header">
								<span class="text-primary-d2 text-120">Knob Input</span>
							</div>

							<div class="card-body">

								<div class="row">
									<div class="col-6 text-center pos-rel">
										<input type="text" class="knob border-0 mw-100 position-center m-auto" value="15" data-min="0" data-max="100" data-step="10" data-width="80" data-height="80" data-thickness=".2" data-fgcolor="rgb(55, 149, 218)" />
									</div>

									<div class="col-6 text-center pos-rel">
										<input type="text" class="knob border-0 mw-100 position-center m-auto" value="41" data-min="0" data-max="100" data-width="80" data-height="80" data-thickness=".2" data-fgcolor="#629A58" data-displayprevious="true" data-anglearc="250" data-angleoffset="-125" />
									</div>
								</div>

								<div class="row mt-3">
									<div class="col-12 text-center pos-rel">
										<input type="text" class="knob border-0 mw-100 position-center m-auto" value="1" data-min="0" data-max="10" data-width="150" data-height="150" data-thickness=".2" data-fgcolor="#94645C" data-angleoffset="90" data-cursor="true" />
									</div>
								</div>

							</div>
						</div>
					</div><!-- /.col -->
				</div><!-- /.row -->






			</form>



		</div> <!-- /.body-container-->














		<!-- include common vendor scripts used in demo pages -->
		<script src="../../Template/ace/node_modules/jquery/dist/jquery.js"></script>
		<script src="../../Template/ace/node_modules/popper.js/dist/umd/popper.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap/dist/js/bootstrap.js"></script>



		<!-- include vendor scripts used in "Horizontal Menu" page. see "/views//pages/partials/horizontal/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/chart.js/dist/Chart.js"></script>
		<script src="../../Template/ace/node_modules/fullcalendar/main.js"></script>


		<!-- include vendor scripts used in "Dashboard 4" page. see "/views//pages/partials/dashboard-4/@vendor-scripts.hbs" -->
		<!-- <script src="../Template/ace/node_modules/chart.js/dist/Chart.js"></script>

		<script src="../Template/ace/node_modules/fullcalendar/main.js"></script> -->

		<script src="../../Template/ace/node_modules/flot/dist/es5/jquery.flot.js"></script>


		<!-- <script src="../Template/ace/node_modules/chartist/dist/chartist.js"></script> -->


		<!-- <script src="https://www.gstatic.com/charts/loader.js"></script> -->

		<script src="../../Template/ace/node_modules/sortablejs/Sortable.js"></script>

		<!-- include vendor scripts used in "Modals" page. see "/views//pages/partials/modals/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/bootbox/bootbox.all.js"></script>
		<!-- include vendor scripts used in "Calendar" page. see "/views//pages/partials/calendar/@vendor-scripts.hbs" -->
		<!-- <script src="../Template/ace/node_modules/fullcalendar/main.js"></script> -->




		<!-- include vendor scripts used in "Basic Elements" page. see "/views//pages/partials/form-basic/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/autosize/dist/autosize.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-maxlength/dist/bootstrap-maxlength.js"></script>
		<script src="../../Template/ace/node_modules/inputmask/dist/jquery.inputmask.js"></script>
		<script src="../../Template/ace/node_modules/nouislider/distribute/nouislider.js"></script>
		<script src="../../Template/ace/node_modules/ion-rangeslider/js/ion.rangeSlider.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.js"></script>
		<script src="../../Template/ace/node_modules/tiny-date-picker/dist/date-range-picker.js"></script>
		<script src="../../Template/ace/node_modules/moment/moment.js"></script>
		<script src="../../Template/ace/node_modules/eonasdan-bootstrap-datetimepicker/src/js/bootstrap-datetimepicker.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.js"></script>
		<script src="../../Template/ace/node_modules/es6-object-assign/dist/object-assign-auto.js"></script>
		<script src="../../Template/ace/node_modules/@jaames/iro/dist/iro.js"></script>
		<script src="../../Template/ace/node_modules/jquery-knob/dist/jquery.knob.min.js"></script>

		<!-- include vendor scripts used in "Gallery" page. see "/views//pages/partials/gallery/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/photoswipe/dist/photoswipe.js"></script>
		<script src="../../Template/ace/node_modules/photoswipe/dist/photoswipe-ui-default.js"></script>

		<!-- include vendor scripts used in "DataTables" page. see "/views//pages/partials/table-datatables/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/datatables/media/js/jquery.dataTables.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-bs4/js/dataTables.bootstrap4.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-colreorder/js/dataTables.colReorder.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-select/js/dataTables.select.js"></script>


		<script src="../../Template/ace/node_modules/datatables.net-buttons/js/dataTables.buttons.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-buttons-bs4/js/buttons.bootstrap4.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-buttons/js/buttons.html5.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-buttons/js/buttons.print.js"></script>
		<script src="../../Template/ace/node_modules/datatables.net-buttons/js/buttons.colVis.js"></script>


		<script src="../../Template/ace/node_modules/datatables.net-responsive/js/dataTables.responsive.js"></script>


		<!-- include vendor scripts used in "More Elements" page. see "/views//pages/partials/form-more/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/bootstrap-star-rating/js/star-rating.js"></script>

		<script src="../../Template/ace/node_modules/typeahead.js/dist/typeahead.bundle.js"></script>

		<script src="../../Template/ace/node_modules/bootstrap-select/dist/js/bootstrap-select.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.js"></script>


		<script src="../../Template/ace/node_modules/select2/dist/js/select2.js"></script>
		<script src="../../Template/ace/node_modules/chosen-js/chosen.jquery.js"></script>


		<script src="../../Template/ace/node_modules/bootstrap-tagsinput/dist/bootstrap-tagsinput.js"></script>

		<!-- include vendor scripts used in "Profile" page. see "/views//pages/partials/page-profile/@vendor-scripts.hbs" -->


		<script src="../../Template/ace/node_modules/sortablejs/Sortable.js"></script>

		<!-- include vendor scripts used in "Alerts" page. see "/views//pages/partials/alerts/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/sweetalert2/dist/sweetalert2.all.js"></script>
		<script src="../../Template/ace/node_modules/interactjs/dist/interact.js"></script>


		<!-- include vendor scripts used in "Bootstrap Table" page. see "/views//pages/partials/table-bootstrap/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/tableexport.jquery.plugin/tableExport.min.js"></script>


		<!-- "More Elements" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/form-more/@page-script.js"></script>


		<script src="../../Template/ace/node_modules/bootstrap-table/dist/bootstrap-table.min.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-table/dist/extensions/export/bootstrap-table-export.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-table/dist/extensions/print/bootstrap-table-print.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-table/dist/extensions/mobile/bootstrap-table-mobile.js"></script>





		<!-- include ../../Template/ace.js -->
		<script src="../../Template/ace/dist/js/ace.js"></script>


		<!-- demo.js is only for ../Template/ace's demo and you shouldn't use it -->
		<!-- <script src="../Template/ace/app/browser/demo.js"></script> -->



		<!-- "Dashboard 4" page script to enable its demo functionality -->
		<!-- <script src="../Template/ace/views/pages/dashboard-4/@page-script.js"></script> -->



		<!-- "Basic Elements" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/form-basic/@page-script.js"></script>

		<!-- "Icons" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/icons/@page-script.js"></script>

		<!-- "Modals" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/modals/@page-script.js"></script>

		<!-- "Gallery" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/gallery/@page-script.js"></script>
		<!-- "DataTables" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/table-datatables/@page-script.js"></script>

		<!-- "Profile" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/page-profile/@page-script.js"></script>

		<!-- "Alerts" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/alerts/@page-script.js"></script>

		<!-- "Bootstrap Table" page script to enable its demo functionality -->
		<script src="../../Template/ace/views/pages/table-bootstrap/@page-script.js"></script>


		<!-- include vendor scripts used in "File Upload" page. see "/views//pages/partials/form-upload/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/dropzone/dist/dropzone.js"></script>





		<!-- "Calendar" page script to enable its demo functionality -->
		<!-- <script src="../Template/ace/views/pages/calendar/@page-script.js"></script> -->

		<!-- <script src="calendar/@page-script.js"></script> -->

		<!-- "Dashboard" page script to enable its demo functionality -->
		<!-- <script src="../Template/ace/views/pages/dashboard/@page-script.js"></script>
		<script src="../Template/ace/views/pages/dashboard-2/@page-script.js"></script>
		<script src="../Template/ace/views/pages/dashboard-3/@page-script.js"></script>
		<script src="../Template/ace/views/pages/dashboard-4/@page-script.js"></script> -->

		<!-- <script src="../Template/ace/views/pages/charts/@page-script.js"></script> -->
		<!-- <script src="charts/@page-script-data.js"></script> -->
		<!-- <script type="text/javascript" src="assets/js/sweetalert.min.js"></script> -->
		<!-- "Horizontal Menu" page script to enable its demo functionality -->

		<!-- include vendor scripts used in "Wysiwyg & Markdown" page. see "/views//pages/partials/form-wysiwyg/@vendor-scripts.hbs" -->
		<script src="../../Template/ace/node_modules/summernote/dist/summernote-lite.js"></script>

		<script src="../../Template/ace/node_modules/jquery.hotkeys/jquery.hotkeys.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js"></script>


		<script src="../../Template/ace/node_modules/markdown/lib/markdown.js"></script>
		<script src="../../Template/ace/node_modules/bootstrap-markdown/js/bootstrap-markdown.js"></script>
		<script src="../../Template/ace/views/pages/form-wysiwyg/@page-script.js"></script>

		<script src="../../Template/ace/views/pages/dashboard-2/@page-script.js"></script>

		<script src="../../Template/ace/views/pages/form-upload/@page-script.js"></script>




		<script type="text/javascript">
			DecoupledEditor
				.create(document.querySelector('#editor'))
				.then(editor => {
					const toolbarContainer = document.querySelector('#toolbar-container');

					toolbarContainer.appendChild(editor.ui.view.toolbar.element);
				})
				.catch(error => {
					console.error(error);
				});
		</script>

		<script>
			$('#datepicker').datepicker({
				uiLibrary: 'bootstrap'
			});
		</script>

		<script type="text/javascript">
			// datepicker

			// datepicker
			var TinyDatePicker = DateRangePicker.TinyDatePicker;
			TinyDatePicker("#dates_1", {
				mode: "dp-below",
			}).on("statechange", function(ev) {});

			var TinyDatePicker = DateRangePicker.TinyDatePicker;
			TinyDatePicker("#dates_2", {
				mode: "dp-below",
			}).on("statechange", function(ev) {});
			var TinyDatePicker = DateRangePicker.TinyDatePicker;
			TinyDatePicker("#dates_3", {
				mode: "dp-below",
			}).on("statechange", function(ev) {});
			var TinyDatePicker = DateRangePicker.TinyDatePicker;
			TinyDatePicker("#dates_4", {
				mode: "dp-below",
			}).on("statechange", function(ev) {});
		</script>

		<script type="text/javascript">
			function openForm() {
				document.getElementById("myForm").style.display = "block";
			}

			function closeForm() {
				document.getElementById("myForm").style.display = "none";
			}
		</script>


	</body>

	</html>
<?php  } else {
	echo "<center><font size=+2></font>กรุณาลองใหม่อีกครั้งค่ะ1</font></center>";
	echo "<meta http-equiv='refresh' content='1; url=index.php'>";
}
?>
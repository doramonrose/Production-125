<?php
session_start();
include("../../config/connect_db.php");
include("../../config/config_dates.php");
include("../../config/main_function.php");
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

	$s_prefix = mysqli_query($db_person, "select * from prefix where code='$row[prefix_code]' ");
	$r_prefix = mysqli_fetch_array($s_prefix);
	$s_position = mysqli_query($db_person, "select * from position where code='$row[position_code]' ");
	$r_position = mysqli_fetch_array($s_position);
	$s_position_dean = mysqli_query($db_person, "select * from position_dean where code='$row[position_dean]' ");
	$r_position_dean = mysqli_fetch_array($s_position_dean);

	$s_staff_mis = mysqli_query($db_person, "select * from staff_mis where code='$row[code]'");
	$r_staff_mis = mysqli_fetch_array($s_staff_mis);

	$page = $_GET['page'];
	$op = $_GET['op'];
	$pages = $_GET['pages'];



	if ($page == "") {
		$page = 'main';
	}
	//$pagee=$_GET['pagee'];
	$active = 'active';

	//echo "$page";

	$email = $row['email'];
	$email_exe = $row['email_exe'];
	$code_user = $row['code'];



?>
	<!doctype html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
		<!-- <base href=".../Template/ace/" /> -->

		<title><?= $title; ?></title>

		<!-- include common vendor stylesheets & fontawesome -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/bootstrap/dist/css/bootstrap.css">

		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/@fortawesome/fontawesome-free/css/fontawesome.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/@fortawesome/fontawesome-free/css/regular.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/@fortawesome/fontawesome-free/css/brands.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/@fortawesome/fontawesome-free/css/solid.css">




		<!-- include vendor stylesheets used in "Dashboard 4" page. see "/views//pages/partials/dashboard-4/@vendor-stylesheets.hbs" -->
		<!-- "Dashboard" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/views/pages/dashboard-3/@page-style.css">
		<!-- "Charts" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/views/pages/charts/@page-style.css">

		<!-- include vendor stylesheets used in "Horizontal Menu" page. see "/views//pages/partials/horizontal/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/fullcalendar/main.css">

		<!-- include fonts -->
		<!-- <link rel="stylesheet" type="text/css" href="../Template/../Template/ace/dist/css/../Template/../Template/ace-font.css"> -->
		<!-- <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" /> -->
		<link href="https://fonts.googleapis.com/css?family=Kanit:300|Mitr&display=swap" rel="stylesheet">


		<!-- include vendor stylesheets used in "Basic Elements" page. see "/views//pages/partials/form-basic/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/nouislider/distribute/nouislider.min.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/ion-rangeslider/css/ion.rangeSlider.min.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/tiny-date-picker/tiny-date-picker.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/tiny-date-picker/date-range-picker.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.css">


		<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
		<script src="https://unpkg.com/gijgo@1.9.13/js/gijgo.min.js" type="text/javascript"></script>
		<link href="https://unpkg.com/gijgo@1.9.13/css/gijgo.min.css" rel="stylesheet" type="text/css" />


		<!-- include vendor stylesheets used in "Calendar" page. see "/views//pages/partials/calendar/@vendor-stylesheets.hbs" -->
		<!-- <link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/fullcalendar/main.css"> -->

		<!-- ../Template/ace.css -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/dist/css/ace.css">

		<!-- favicon -->
		<link rel="icon" type="image/png" href="../Template/ace/assets/mis.jpg" />

		<!-- include vendor stylesheets used in "Charts" page. see "/views//pages/partials/charts/@vendor-stylesheets.hbs" -->
		<!-- <link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/chartist/dist/chartist.min.css"> -->

		<!-- include vendor stylesheets used in "Icons" page. see "/views//pages/partials/icons/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/jam-icons/css/jam.min.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/eva-icons/style/eva-icons.css">

		<!-- "Profile" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/views/pages/page-profile/@page-style.css">

		<!-- include vendor stylesheets used in "Buttons" page. see "/views//pages/partials/buttons/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/node-waves/dist/waves.css">


		<!-- include vendor stylesheets used in "Gallery" page. see "/views//pages/partials/gallery/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/photoswipe/dist/photoswipe.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/photoswipe/dist/default-skin/default-skin.css">

		<!-- include vendor stylesheets used in "DataTables" page. see "/views//pages/partials/table-datatables/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/datatables.net-bs4/css/dataTables.bootstrap4.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/datatables.net-buttons-bs4/css/buttons.bootstrap4.css">

		<!-- "DataTables" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/views/pages/table-datatables/@page-style.css">

		<!-- include vendor stylesheets used in "Bootstrap Table" page. see "/views//pages/partials/table-bootstrap/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/bootstrap-table/dist/bootstrap-table.css">


		<!-- include vendor stylesheets used in "More Elements" page. see "/views//pages/partials/form-more/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/bootstrap-star-rating/css/star-rating.css">

		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/bootstrap-select/dist/css/bootstrap-select.css">

		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/bootstrap-duallistbox/dist/bootstrap-duallistbox.css">


		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/select2/dist/css/select2.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/chosen-js/chosen.css">



		<!-- "Horizontal Menu" page styles, specific to this page for demo only -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/views/pages/horizontal/@page-style.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/dist/css/ace-themes.css">


		<!-- "Dashboard 4" page styles, specific to this page for demo only -->
		<!-- <link rel="stylesheet" type="text/css" href="../Template/ace/views/pages/dashboard-4/@page-style.css"> -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/dist/css/ace-themes.css">

		<!-- include vendor stylesheets used in "Wysiwyg & Markdown" page. see "/views//pages/partials/form-wysiwyg/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/summernote/dist/summernote-lite.css">

		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/bootstrap-markdown/css/bootstrap-markdown.min.css">
		<link rel="stylesheet" type="text/css" href="../Template/ace/views/pages/form-wysiwyg/@page-style.css">

		<!-- include vendor stylesheets used in "File Upload" page. see "/views//pages/partials/form-upload/@vendor-stylesheets.hbs" -->
		<link rel="stylesheet" type="text/css" href="../Template/ace/node_modules/dropzone/dist/dropzone.css">
		<link rel="stylesheet" href="chatbot/chat.css">

		<link rel="stylesheet" href="chatbot/bot.css">
		<!-- ckeditor -->
		<script type="text/javascript" src="ckeditor2/ckeditor.js"></script>
		<script type="text/javascript" src="assets/js/sweetalert.min.js"></script>

		<script>
			function check() {
				var elem = document.getElementById('visa').value;
				if (!elem.match(/^([a-z0-9])+$/i)) {
					alert("กรอกได้เฉพาะตัวอักษรภาษาอังกฤษเท่านั้น");
					document.getElementById('visa').value = "";
				}
			}

			$('.fileInput').aceFileInput({
				btnChooseClass: 'bgc-grey-l2 pt-15 px-2 my-1px mr-1px',
				btnChooseText: 'เลือกไฟล์',
				btnChangeText: 'เปลี่ยนไฟล์',
				placeholderText: 'ยังไม่ได้เลือกไฟล์',
				placeholderIcon: '<i class="fa fa-file bgc-blue-m1 text-white w-4 py-2 text-center"></i>'
			});
		</script>
	</head>

	<body style="font-family: 'Kanit', sans-serif;">
		<div class="body-container">
			<nav class="navbar navbar-sm navbar-fixed-xl navbar-expand-lg navbar-white">
				<div class="navbar-inner shadow-md">
					<div class="container container-plus">


						<div class="navbar-intro justify-content-xl-start bgc-transparent pr-lg-3 w-auto">

							<button type="button" class="btn btn-burger burger-arrowed static collapsed ml-2 d-flex d-xl-none btn-h-white" data-toggle-mobile="sidebar" data-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle sidebar">
								<span class="bars text-dark-tp5"></span>
							</button><!-- mobile sidebar toggler button -->

							<button type="button" class="btn btn-burger burger-compact align-self-center ml-2 d-none d-xl-flex btn-h-light-primary" data-toggle="sidebar" data-toggle-class="collapsed-h" data-target="#sidebar" aria-controls="sidebar" aria-expanded="true" aria-label="Toggle sidebar">
								<span class="bars text-dark-tp5"></span>
							</button><!-- sidebar toggler button -->

							<a class="navbar-brand text-dark-l1 ml-lg-1" href="#">
								<!-- <i class="fa fa-leaf text-success"></i> -->
								<span class="text-green">Pharmacy</span>
								<span><strong>
										<font color=blue> H</font>
										<font color=orange> R</font>

									</strong>
								</span>

							</a><!-- /.navbar-brand -->

						</div><!-- /.navbar-intro -->


						<!-- breadcrumbs -->
						<div class="navbar-content d-none d-xl-flex">
							<ol class="breadcrumb pl-2 ml-3">
								<li class="breadcrumb-item"><a class="text-dark-l4 text-120" href="#">PHARMACY KPI : ระบบสารสนเทศบุคลากรคณะเภสัชศาสตร์ มหาวิทยาลัยเชียงใหม่</a></li>

							</ol>
						</div>




						<!-- mobile #navbarMenu toggler button -->
						<button class="navbar-toggler ml-1 mr-2 px-1" type="button" data-toggle="collapse" data-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navbar menu">
							<span class="pos-rel">
								<img class="radius-3px mr-1 brc-grey-m2 border-1" width="36" src="<?= $path_picture; ?>/<?= $row['photo']; ?>">
								<span class="badge badge-dot bgc-orange-d1 position-tr mr-1px mt-n1"></span>
							</span>
						</button>


						<div class="navbar-menu collapse navbar-collapse navbar-backdrop" id="navbarMenu">

							<div class="navbar-nav">
								<ul class="nav nav-compact has-active-border">


									<li class="nav-item dropdown order-first order-lg-last dropdown-hover">
										<a class="nav-link dropdown-toggle px-lg-2 ml-lg-3" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
											<img id="id-navbar-user-image" class="d-none d-lg-inline-block radius-3px mr-1 brc-grey-m2 border-1" src="<?= $path_picture; ?>/<?= $row['photo']; ?>" height="42">

											<span class="d-inline-block d-lg-none"><?= $row['fname'] ?>
												<?= $row['lname'] ?></span><!-- show only on mobile -->

											<i class="caret fa fa-ellipsis-v d-none d-xl-block"></i>
											<i class="caret fa fa-angle-left d-block d-lg-none"></i>
										</a>

										<div class="dropdown-menu dropdown-caret dropdown-menu-right brc-primary-m3">


											<a class="dropdown-item btn btn-outline-grey btn-h-light-orange btn-a-light-orange" href="logout.php">
												<i class="fa fa-power-off text-orange-d2 text-105 mr-1"></i>
												Logout
											</a>
										</div>
									</li><!-- /.nav-item:last -->

								</ul><!-- /.navbar-nav menu -->
							</div><!-- /.navbar-nav -->

						</div><!-- /.navbar-menu.navbar-collapse -->


					</div><!-- /.container -->
				</div><!-- /.navbar-inner -->
			</nav>
			<div id="sidebar" class="sidebar sidebar-fixed sidebar-hover sidebar-h sidebar-white sidebar-top" data-swipe="true" data-backdrop="true" data-dismiss="true">
				<div class="sidebar-inner border-r-0 border-b-1 brc-secondary-l2 shadow-md">
					<div class="container container-plus px-0 d-flex flex-column flex-xl-row">

						<div class="flex-grow-1 d-xl-flex flex-xl-row ace-scroll" data-ace-scroll="{}">
							<!-- ace-scroll is not applied in desktop view, but it's used in mobile view -->
							<div class="sidebar-section">
								<div class="sidebar-section-item fadeable-below fadeable-center px-3">

									<div class="fadeinable sidebar-shortcuts-mini w-auto" role="button">
										<div>
											<div>
												<span class="btn btn-success p-0"></span><span class="btn btn-blue p-0"></span>
											</div>
											<div class="mt-n2">
												<span class="btn btn-purple p-0"></span><span class="btn btn-orange p-0"></span>
											</div>
										</div>
									</div>



								</div>
							</div>

							<ul class="nav nav-spaced nav-fill text-center nav-active-sm has-active-border active-on-top">


								<li class="nav-item-caption">
									<span class="fadeable pl-3">MAIN</span>
									<span class="fadeinable mt-n2 text-125">&hellip;</span>
									<!--
                			OR something like the following (with `.hideable` text)
                		-->
									<!--
                			<div class="hideable">
                				<span class="pl-3">MAIN</span>
                			</div>
                			<span class="fadeinable mt-n2 text-125">&hellip;</span>
                		-->
								</li>


								<li class="nav-item <?php if ($page == 'main') {
														echo $active;
													} ?>">

									<a href="?page=main" class="nav-link">
										<i class="nav-icon fa fa-home"></i>
										<span class="nav-text fadeable">
											<!-- <span>Home</span> -->
										</span>


									</a>

									<b class="sub-arrow"></b>

								</li>


								<li class="nav-item <?php if ($page == 'dashboard') {
														echo $active;
													} ?>">

									<a href="?page=dashboard" class="nav-link ">
										<i class="nav-icon fa fa-cube"></i>
										<span class="nav-text fadeable">
											<span>Dashboard</span>
										</span>



										<!-- or you can use custom icons. first add `d-style` to 'A' -->
										<!--
                	 	<b class="caret d-n-collapsed fa fa-minus text-80"></b>
                	 	<b class="caret d-collapsed fa fa-plus text-80"></b>
                	 -->
									</a>



									<b class="sub-arrow"></b>

								</li>


								<li class="nav-item <?php if ($page == 'profile') {
														echo $active;
													} ?>">

									<a href="#" class="nav-link dropdown-toggle collapsed">
										<i class="nav-icon fa fa-user"></i>
										<span class="nav-text fadeable">
											<span>ข้อมูลพื้นฐาน</span>
										</span>

										<b class="caret fa fa-angle-left rt-n90"></b>

										<!-- or you can use custom icons. first add `d-style` to 'A' -->
										<!--
                	 	<b class="caret d-n-collapsed fa fa-minus text-80"></b>
                	 	<b class="caret d-collapsed fa fa-plus text-80"></b>
                	 -->
									</a>

									<div class="hideable submenu collapse">
										<ul class="submenu-inner">

											<li class="nav-item">

												<a href="?page=profile" class="nav-link">

													<span class="nav-text">
														<span>รอว่างก่อนน่ะค่ะ</span>
													</span>


												</a>

											</li>




											<!-- **** -->


										</ul>
									</div>

									<b class="sub-arrow"></b>

								</li>


								<li class="nav-item">

									<a href="?page=person&type=teacher" class="nav-link dropdown-toggle collapsed">
										<i class="nav-icon fa fa-user-graduate"></i>
										<span class="nav-text fadeable">
											<span>บุคลากรสายวิชาการ</span>
										</span>

										<b class="caret fa fa-angle-left rt-n90"></b>


									</a>
									<div class="hideable submenu collapse">
										<ul class="submenu-inner">
											<li class="nav-item">
												<a href="?page=person&type=teacher&duty=1" class="nav-link">
													<span class="nav-text">
														<span>ข้าราชการ</span>
													</span>
												</a>
											</li>
											<li class="nav-item">
												<a href="?page=person&type=teacher&duty=4" class="nav-link">

													<span class="nav-text">
														<span>พนักงานมหาวิทยาลัย</span>
													</span>
												</a>
											</li>
										</ul>
									</div>


									<b class="sub-arrow"></b>

								</li>
								<li class="nav-item">
									<a href="?page=person&type=staff" class="nav-link dropdown-toggle collapsed">
										<i class="nav-icon fas fa-users"></i>

										<span class="nav-text fadeable">
											<span>บุคลากรสายสนับสนุน</span>
										</span>

										<b class="caret fa fa-angle-left rt-n90"></b>
									</a>
									<div class="hideable submenu collapse">
										<ul class="submenu-inner">
											<li class="nav-item">
												<a href="?page=person&type=staff&duty=7" class="nav-link">
													<span class="nav-text">
														<span>ลูกจ้างประจำ</span>
													</span>
												</a>
											</li>
											<li class="nav-item">
												<a href="?page=person&type=staff&duty=5" class="nav-link">

													<span class="nav-text">
														<span>พนักงานมหาวิทยาลัย</span>
													</span>
												</a>
											</li>
											<li class="nav-item">
												<a href="?page=person&type=staff&duty=6" class="nav-link">

													<span class="nav-text">
														<span>พนักงานมหาวิทยาลัยชั่วคราว</span>
													</span>
												</a>
											</li>
											<li class="nav-item">
												<a href="?page=person&type=staff&duty=10" class="nav-link">

													<span class="nav-text">
														<span>พนักงานโครงการของส่วนงาน</span>
													</span>
												</a>
											</li>
										</ul>
									</div>


									<b class="sub-arrow"></b>

								</li>


								<li class="nav-item">
									<a href="?page=person&type=staff" class="nav-link dropdown-toggle collapsed">
										<i class="nav-icon fas fa-paper-plane"></i>

										<span class="nav-text fadeable">
											<span>API กองบุคคล</span>
										</span>

										<b class="caret fa fa-angle-left rt-n90"></b>
									</a>
									<div class="hideable submenu collapse">
										<ul class="submenu-inner">
											<li class="nav-item">
												<a href="?page=api_hr&type=ข้าราชการ" class="nav-link">
													<span class="nav-text">
														<span>ข้าราชการ</span>
													</span>
												</a>
											</li>
											<li class="nav-item">
												<a href="?page=api_hr&type=ลูกจ้างประจำ" class="nav-link">
													<span class="nav-text">
														<span>ลูกจ้างประจำ</span>
													</span>
												</a>
											</li>
											<li class="nav-item">
												<a href="?page=api_hr&type=พนักงานมหาวิทยาลัยประจำ" class="nav-link">

													<span class="nav-text">
														<span>พนักงานมหาวิทยาลัยประจำ </span>
													</span>
												</a>
											</li>

											<li class="nav-item">
												<a href="?page=api_hr&type=พนักงานมหาวิทยาลัยชั่วคราว" class="nav-link">

													<span class="nav-text">
														<span>พนักงานมหาวิทยาลัยชั่วคราว</span>
													</span>
												</a>
											</li>
											<li class="nav-item">
												<a href="?page=api_hr&type=พนักงานโครงการของส่วนงาน" class="nav-link">

													<span class="nav-text">
														<span>พนักงานโครงการของส่วนงาน</span>
													</span>
												</a>
											</li>
										</ul>
									</div>


									<b class="sub-arrow"></b>

								</li>




							</ul>
						</div>

						<div class="sidebar-section d-none d-xl-flex ml-xl-auto pl-xl-4">
							<!-- the logout and settings button, only shown in desktop view -->
							<div class="sidebar-section-item fadeable-below fadeable-center">
								<div class="fadeinable w-auto">
									<a href="logout.php" title="Logout" class="btn btn-outline-blue btn-brc-tp radius-3px py-2">
										<i class="fa fa-power-off text-140"></i>
									</a>
								</div>
							</div>


						</div>



					</div><!-- .container -->
				</div><!-- .sidebar-inner -->
			</div><!-- .sidebar -->

			<div class="main-container">

				<div role="main" class="main-content">
					<div class="page-content container container-plus px-md-4 px-xl-5">

						<?php
						include($page . ".php");
						?>

					</div><!-- page-content -->


					<div class="footer-tools">
						<a href="#" class="btn-scroll-up btn btn-green px-25 py-2 text-95 radius-1 mb-2 mr-2">
							<i class="fa fa-angle-double-up w-2 h-2"></i>
						</a>
					</div>

				</div><!-- main-content -->



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


			</div><!-- main-container -->




		</div> <!-- /.body-container-->














		<!-- include common vendor scripts used in demo pages -->
		<script src="../Template/ace/node_modules/jquery/dist/jquery.js"></script>
		<script src="../Template/ace/node_modules/popper.js/dist/umd/popper.js"></script>
		<script src="../Template/ace/node_modules/bootstrap/dist/js/bootstrap.js"></script>



		<!-- include vendor scripts used in "Horizontal Menu" page. see "/views//pages/partials/horizontal/@vendor-scripts.hbs" -->
		<script src="../Template/ace/node_modules/chart.js/dist/Chart.js"></script>
		<script src="../Template/ace/node_modules/fullcalendar/main.js"></script>


		<!-- include vendor scripts used in "Dashboard 4" page. see "/views//pages/partials/dashboard-4/@vendor-scripts.hbs" -->
		<!-- <script src="../Template/ace/node_modules/chart.js/dist/Chart.js"></script>

		<script src="../Template/ace/node_modules/fullcalendar/main.js"></script> -->

		<script src="../Template/ace/node_modules/flot/dist/es5/jquery.flot.js"></script>


		<!-- <script src="../Template/ace/node_modules/chartist/dist/chartist.js"></script> -->


		<!-- <script src="https://www.gstatic.com/charts/loader.js"></script> -->

		<script src="../Template/ace/node_modules/sortablejs/Sortable.js"></script>

		<!-- include vendor scripts used in "Modals" page. see "/views//pages/partials/modals/@vendor-scripts.hbs" -->
		<script src="../Template/ace/node_modules/bootbox/bootbox.all.js"></script>
		<!-- include vendor scripts used in "Calendar" page. see "/views//pages/partials/calendar/@vendor-scripts.hbs" -->
		<!-- <script src="../Template/ace/node_modules/fullcalendar/main.js"></script> -->




		<!-- include vendor scripts used in "Basic Elements" page. see "/views//pages/partials/form-basic/@vendor-scripts.hbs" -->
		<script src="../Template/ace/node_modules/autosize/dist/autosize.js"></script>
		<script src="../Template/ace/node_modules/bootstrap-maxlength/dist/bootstrap-maxlength.js"></script>
		<script src="../Template/ace/node_modules/inputmask/dist/jquery.inputmask.js"></script>
		<script src="../Template/ace/node_modules/nouislider/distribute/nouislider.js"></script>
		<script src="../Template/ace/node_modules/ion-rangeslider/js/ion.rangeSlider.js"></script>
		<script src="../Template/ace/node_modules/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.js"></script>
		<script src="../Template/ace/node_modules/tiny-date-picker/dist/date-range-picker.js"></script>
		<script src="../Template/ace/node_modules/moment/moment.js"></script>
		<script src="../Template/ace/node_modules/eonasdan-bootstrap-datetimepicker/src/js/bootstrap-datetimepicker.js"></script>
		<script src="../Template/ace/node_modules/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.js"></script>
		<script src="../Template/ace/node_modules/es6-object-assign/dist/object-assign-auto.js"></script>
		<script src="../Template/ace/node_modules/@jaames/iro/dist/iro.js"></script>
		<script src="../Template/ace/node_modules/jquery-knob/dist/jquery.knob.min.js"></script>

		<!-- include vendor scripts used in "Gallery" page. see "/views//pages/partials/gallery/@vendor-scripts.hbs" -->
		<script src="../Template/ace/node_modules/photoswipe/dist/photoswipe.js"></script>
		<script src="../Template/ace/node_modules/photoswipe/dist/photoswipe-ui-default.js"></script>

		<!-- include vendor scripts used in "DataTables" page. see "/views//pages/partials/table-datatables/@vendor-scripts.hbs" -->
		<script src="../Template/ace/node_modules/datatables/media/js/jquery.dataTables.js"></script>
		<script src="../Template/ace/node_modules/datatables.net-bs4/js/dataTables.bootstrap4.js"></script>
		<script src="../Template/ace/node_modules/datatables.net-colreorder/js/dataTables.colReorder.js"></script>
		<script src="../Template/ace/node_modules/datatables.net-select/js/dataTables.select.js"></script>


		<script src="../Template/ace/node_modules/datatables.net-buttons/js/dataTables.buttons.js"></script>
		<script src="../Template/ace/node_modules/datatables.net-buttons-bs4/js/buttons.bootstrap4.js"></script>
		<script src="../Template/ace/node_modules/datatables.net-buttons/js/buttons.html5.js"></script>
		<script src="../Template/ace/node_modules/datatables.net-buttons/js/buttons.print.js"></script>
		<script src="../Template/ace/node_modules/datatables.net-buttons/js/buttons.colVis.js"></script>


		<script src="../Template/ace/node_modules/datatables.net-responsive/js/dataTables.responsive.js"></script>


		<!-- include vendor scripts used in "More Elements" page. see "/views//pages/partials/form-more/@vendor-scripts.hbs" -->
		<script src="../Template/ace/node_modules/bootstrap-star-rating/js/star-rating.js"></script>

		<script src="../Template/ace/node_modules/typeahead.js/dist/typeahead.bundle.js"></script>

		<script src="../Template/ace/node_modules/bootstrap-select/dist/js/bootstrap-select.js"></script>
		<script src="../Template/ace/node_modules/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.js"></script>


		<script src="../Template/ace/node_modules/select2/dist/js/select2.js"></script>
		<script src="../Template/ace/node_modules/chosen-js/chosen.jquery.js"></script>


		<script src="../Template/ace/node_modules/bootstrap-tagsinput/dist/bootstrap-tagsinput.js"></script>

		<!-- include vendor scripts used in "Profile" page. see "/views//pages/partials/page-profile/@vendor-scripts.hbs" -->


		<script src="../Template/ace/node_modules/sortablejs/Sortable.js"></script>

		<!-- include vendor scripts used in "Alerts" page. see "/views//pages/partials/alerts/@vendor-scripts.hbs" -->
		<script src="../Template/ace/node_modules/sweetalert2/dist/sweetalert2.all.js"></script>
		<script src="../Template/ace/node_modules/interactjs/dist/interact.js"></script>


		<!-- include vendor scripts used in "Bootstrap Table" page. see "/views//pages/partials/table-bootstrap/@vendor-scripts.hbs" -->
		<script src="../Template/ace/node_modules/tableexport.jquery.plugin/tableExport.min.js"></script>


		<!-- "More Elements" page script to enable its demo functionality -->
		<script src="../Template/ace/views/pages/form-more/@page-script.js"></script>


		<script src="../Template/ace/node_modules/bootstrap-table/dist/bootstrap-table.min.js"></script>
		<script src="../Template/ace/node_modules/bootstrap-table/dist/extensions/export/bootstrap-table-export.js"></script>
		<script src="../Template/ace/node_modules/bootstrap-table/dist/extensions/print/bootstrap-table-print.js"></script>
		<script src="../Template/ace/node_modules/bootstrap-table/dist/extensions/mobile/bootstrap-table-mobile.js"></script>





		<!-- include ../Template/ace.js -->
		<script src="../Template/ace/dist/js/ace.js"></script>


		<!-- demo.js is only for ../Template/ace's demo and you shouldn't use it -->
		<!-- <script src="../Template/ace/app/browser/demo.js"></script> -->



		<!-- "Dashboard 4" page script to enable its demo functionality -->
		<!-- <script src="../Template/ace/views/pages/dashboard-4/@page-script.js"></script> -->



		<!-- "Basic Elements" page script to enable its demo functionality -->
		<script src="../Template/ace/views/pages/form-basic/@page-script.js"></script>

		<!-- "Icons" page script to enable its demo functionality -->
		<script src="../Template/ace/views/pages/icons/@page-script.js"></script>

		<!-- "Modals" page script to enable its demo functionality -->
		<script src="../Template/ace/views/pages/modals/@page-script.js"></script>

		<!-- "Gallery" page script to enable its demo functionality -->
		<script src="../Template/ace/views/pages/gallery/@page-script.js"></script>
		<!-- "DataTables" page script to enable its demo functionality -->
		<script src="../Template/ace/views/pages/table-datatables/@page-script.js"></script>

		<!-- "Profile" page script to enable its demo functionality -->
		<script src="../Template/ace/views/pages/page-profile/@page-script.js"></script>

		<!-- "Alerts" page script to enable its demo functionality -->
		<script src="../Template/ace/views/pages/alerts/@page-script.js"></script>

		<!-- "Bootstrap Table" page script to enable its demo functionality -->
		<script src="../Template/ace/views/pages/table-bootstrap/@page-script.js"></script>


		<!-- include vendor scripts used in "File Upload" page. see "/views//pages/partials/form-upload/@vendor-scripts.hbs" -->
		<script src="../Template/ace/node_modules/dropzone/dist/dropzone.js"></script>





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
		<script src="../Template/ace/node_modules/summernote/dist/summernote-lite.js"></script>

		<script src="../Template/ace/node_modules/jquery.hotkeys/jquery.hotkeys.js"></script>
		<script src="../Template/ace/node_modules/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js"></script>


		<script src="../Template/ace/node_modules/markdown/lib/markdown.js"></script>
		<script src="../Template/ace/node_modules/bootstrap-markdown/js/bootstrap-markdown.js"></script>
		<script src="../Template/ace/views/pages/form-wysiwyg/@page-script.js"></script>

		<script src="../Template/ace/views/pages/horizontal/@page-script.js"></script>

		<script src="../Template/ace/views/pages/form-upload/@page-script.js"></script>




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
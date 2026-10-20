<?php
session_start();
include("../../../config/connect_db.php");




?>

<body style="font-family: 'Kanit', sans-serif;">
	<div class="body-container">

		<div class="main-container">



			<div role="main" class="main-content">

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





					<div class="row px-2 mt-3">
						<div class="col-12 col-sm-6 col-lg-3 px-2 mb-2 mb-lg-0">

							<div class="bcard h-100 d-flex align-items-center p-3">
								<div>
									<span class="d-inline-block bgc-green-d1 p-3 radius-round text-center border-4 brc-green-l2">
										<i class="fa fa-dollar-sign text-white text-170 w-4 h-4"></i>
									</span>
								</div>




								<div class="ml-3 flex-grow-1">
									<div class="pos-rel">
										<span class="text-dark-tp4 text-140 mr-n2px">
											$
										</span>

										<span class="text-dark-tp3 text-180">
											85k
										</span>

										<span class="text-blue-m1 text-600 text-90 ml-15 text-nowrap">
											8%
											<i class="fa fa-arrow-up"></i>
										</span>

									</div>


									<div class="text-dark-tp4 text-110">
										today&#x27;s earnings
									</div>
								</div>





								<!-- use a dropdown with tooltips for first info box -->
								<div class="position-tr m-1 dropdown dd-backdrop dd-backdrop-none-md">
									<button type="button"
										class="btn btn-sm btn-brc-tp btn-bgc-tp btn-text-pale btn-light-default dropdown-toggle"
										data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
										<i class="fa fa-ellipsis-h"></i>
									</button>

									<div
										class="dropdown-menu mw-auto dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md">
										<div class="dropdown-inner">
											<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
												href="#" title="Some action" data-toggle="tooltip">
												<i class="fa fa-cog mr-1 text-blue"></i>
												<span class="d-md-none">
													Some action
												</span>
											</a>

											<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
												href="#" title="Another action" data-toggle="tooltip">
												<i class="fas fa-tools text-orange mr-1"></i>
												<span class="d-md-none">
													Another action
												</span>
											</a>
										</div>
									</div>
								</div>

							</div><!-- /.bcard -->

						</div><!-- /.col -->
						<div class="col-12 col-sm-6 col-lg-3 px-2 mb-2 mb-lg-0">

							<div class="bcard h-100 d-flex align-items-center p-3">
								<div>
									<span class="d-inline-block bgc-warning-d1 p-3 radius-round text-center border-4 brc-warning-l2">
										<i class="fa fa-shopping-cart text-white text-170 w-4 h-4"></i>
									</span>
								</div>




								<div class="ml-3 flex-grow-1">
									<div class="pos-rel">

										<span class="text-dark-tp3 text-180">
											369
										</span>


										<span class="text-danger-m2 text-600 text-90 ml-15 text-nowrap">
											2%
											<i class="fa fa-arrow-down"></i>
										</span>
									</div>


									<div class="text-dark-tp4 text-110">
										new orders
									</div>
								</div>





								<div class="position-tr m-1">
									<!-- the dropdown used in some boxes (cards) -->
									<div class="dropdown dd-backdrop dd-backdrop-none-md">
										<button type="button"
											class="btn btn-sm btn-text-pale btn-brc-tp btn-outline-lightgrey btn-h-light-default btn-a-light-default dropdown-toggle"
											data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
											<i class="fa fa-ellipsis-h"></i>
										</button>

										<div
											class="dropdown-menu mw-auto dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md">
											<div class="dropdown-inner">
												<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
													href="#">
													<i class="fa fa-cog text-blue mr-1"></i>
													Some action
												</a>

												<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
													href="#">
													<i class="fas fa-tools text-blue mr-1"></i>
													Another action
												</a>
											</div>
										</div>
									</div>
								</div>

							</div><!-- /.bcard -->

						</div><!-- /.col -->
						<div class="col-12 col-sm-6 col-lg-3 px-2 mb-2 mb-lg-0">

							<div class="bcard h-100 d-flex align-items-center p-3">
								<div>
									<span class="d-inline-block bgc-primary p-3 radius-round text-center border-4 brc-primary-l2">
										<i class="fas fa-chart-line text-white text-170 w-4 h-4"></i>
									</span>
								</div>




								<div class="ml-3 flex-grow-1">
									<div class="pos-rel">

										<span class="text-dark-tp3 text-180">
											520k
										</span>

										<span class="text-blue-m1 text-600 text-90 ml-15 text-nowrap">
											3%
											<i class="fa fa-arrow-up"></i>
										</span>

									</div>


									<div class="text-dark-tp4 text-110">
										page views
									</div>
								</div>


								<div class="mr-auto">
									<canvas class="infobox-line-chart" style="width: 70px;"
										data-values="[1000,800,1800,400,1500,1000,1050,1300,2100,1400,1500,1350]"></canvas>
								</div>



								<div class="position-tr m-1">
									<!-- the dropdown used in some boxes (cards) -->
									<div class="dropdown dd-backdrop dd-backdrop-none-md">
										<button type="button"
											class="btn btn-sm btn-text-pale btn-brc-tp btn-outline-lightgrey btn-h-light-default btn-a-light-default dropdown-toggle"
											data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
											<i class="fa fa-ellipsis-h"></i>
										</button>

										<div
											class="dropdown-menu mw-auto dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md">
											<div class="dropdown-inner">
												<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
													href="#">
													<i class="fa fa-cog text-blue mr-1"></i>
													Some action
												</a>

												<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
													href="#">
													<i class="fas fa-tools text-blue mr-1"></i>
													Another action
												</a>
											</div>
										</div>
									</div>
								</div>

							</div><!-- /.bcard -->

						</div><!-- /.col -->
						<div class="col-12 col-sm-6 col-lg-3 px-2 mb-2 mb-lg-0">

							<div class="bcard h-100 d-flex align-items-center p-3">


								<div class="text-center pos-rel ml-1">
									<canvas class="infobox-piechart" data-value="71"
										style="height: 75px; width: 75px; max-height: 75px; max-width: 75px;"></canvas>
									<span class="position-center text-dark-tp3 text-600">
										71%
									</span>
								</div>


								<div class="ml-3 flex-grow-1">

									<div>
										<span class="text-dark-tp3 text-125">
											Task Progress
										</span>
									</div>

									<div class="text-dark-tp4 text-110">
										3 remaining
									</div>
								</div>





								<div class="position-tr m-1">
									<!-- the dropdown used in some boxes (cards) -->
									<div class="dropdown dd-backdrop dd-backdrop-none-md">
										<button type="button"
											class="btn btn-sm btn-text-pale btn-brc-tp btn-outline-lightgrey btn-h-light-default btn-a-light-default dropdown-toggle"
											data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
											<i class="fa fa-ellipsis-h"></i>
										</button>

										<div
											class="dropdown-menu mw-auto dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md">
											<div class="dropdown-inner">
												<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
													href="#">
													<i class="fa fa-cog text-blue mr-1"></i>
													Some action
												</a>

												<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
													href="#">
													<i class="fas fa-tools text-blue mr-1"></i>
													Another action
												</a>
											</div>
										</div>
									</div>
								</div>

							</div><!-- /.bcard -->

						</div><!-- /.col -->
					</div>





					<div class="row mt-3 mt-lg-4">
						<!-- sale stats chart -->
						<div class="col-12 col-lg-7">
							<div class="bcard card h-100">
								<div class="card-header border-b-1 brc-grey-l3 pb-2">
									<h5 class="card-title font-normal text-120 text-dark-m1 pl-1">
										Sales Stats
									</h5>

									<!-- the dropdowns -->
									<div class="card-toolbar no-border">
										<div class="dropdown dd-backdrop dd-backdrop-none-md">
											<a class="btn btn-light-blue btn-bold btn-xs btn-brc-tp" href="#" role="button"
												data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
												Monthly
											</a>
											<div
												class="dropdown-menu dropdown-menu-right dropdown-caret dropdown-animated dd-slide-up dd-slide-none-md">
												<div class="dropdown-inner">
													<a class="dropdown-item btn btn-h-light-grey btn-a-lighter-blue text-grey-m1 mx-1"
														href="#">Weekly</a>
													<a class="dropdown-item active btn btn-h-light-grey btn-a-lighter-blue btn-a-bold mx-1"
														href="#">Monthly</a>
													<a class="dropdown-item btn btn-h-light-grey btn-a-lighter-blue text-grey-m1 mx-1"
														href="#">Quarterly</a>
													<a class="dropdown-item btn btn-h-light-grey btn-a-lighter-blue text-grey-m1 mx-1"
														href="#">Yearly</a>
												</div>
											</div>
										</div>

										<div class="dropdown dd-backdrop dd-backdrop-none-md">
											<a class="btn btn-light-green btn-bold btn-xs btn-brc-tp ml-1" href="#" role="button"
												data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
												USA
											</a>
											<div
												class="dropdown-menu dropdown-menu-right dropdown-caret dropdown-animated dd-slide-up dd-slide-none-md">
												<div class="dropdown-inner">
													<a class="dropdown-item active btn btn-h-light-grey btn-a-lighter-green btn-a-bold mx-1"
														href="#">USA</a>
													<a class="dropdown-item btn btn-h-light-grey btn-a-lighter-green text-grey-m1 mx-1"
														href="#">Canada</a>
													<a class="dropdown-item btn btn-h-light-grey btn-a-lighter-green text-grey-m1 mx-1"
														href="#">UK</a>
													<a class="dropdown-item btn btn-h-light-grey btn-a-lighter-green text-grey-m1 mx-1"
														href="#">Australia</a>
												</div>
											</div>
										</div>
									</div><!-- /.card-toolbar -->
								</div>

								<div class="card-body h-100 d-flex flex-column justify-content-center py-3 px-2 px-md-4">
									<canvas id="sales-chart" class="mx-n1 mx-md-0 mw-100" style="height: 280px;"></canvas>
								</div>
							</div><!-- /.card -->
						</div>

						<!-- revenue/expense bar chart -->
						<div class="col-12 col-lg-5 mt-3 mt-lg-0 pl-lg-2">
							<div class="bcard card h-100">
								<div class="card-header brc-grey-l3 pb-2">
									<h5 class="card-title font-normal text-120 text-dark-m1 pl-1">
										Revenue vs Expense
									</h5>

									<!-- dropdown menu -->
									<div class="card-toolbar no-border">
										<div class="dropdown dd-backdrop dd-backdrop-none-md">
											<a class="btn btn-sm btn-lighter-secondary text-600 btn-bgc-white brc-secondary-l1 brc-h-secondary-m4 ml-1 dropdown-toggle"
												href="#" role="button" data-toggle="dropdown" data-display="static" aria-haspopup="true"
												aria-expanded="false">
												<i class="far fa-calendar-check mr-2px"></i>
												<span class="text-nowrap">
													Jan 2020
												</span>
												-
												<span class="text-nowrap">
													Jan 2021
												</span>
												<i class="fa fa-caret-down ml-2px"></i>
											</a>
											<div
												class="dropdown-menu dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md">
												<div class="dropdown-inner">
													<a class="dropdown-item m-1 btn btn-sm btn-light-secondary btn-h-light-blue btn-a-blue text-600"
														href="#">Today</a>
													<a class="dropdown-item m-1 btn btn-sm btn-light-secondary btn-h-light-blue btn-a-blue text-600"
														href="#">Yesterday</a>
													<a class="dropdown-item m-1 btn btn-sm btn-light-secondary btn-h-light-blue btn-a-blue text-600"
														href="#">Last Week</a>
													<a class="dropdown-item m-1 btn btn-sm btn-light-secondary btn-h-light-blue btn-a-blue text-600"
														href="#">Last Month</a>
													<a class="dropdown-item m-1 btn btn-sm btn-light-secondary btn-h-light-blue btn-a-blue text-600 active"
														href="#">Custom Range</a>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="card-body p-1 p-md-3 h-100 d-flex align-items-center">
									<canvas id="revenue-expense-chart" class="mx-md-0" height="140"></canvas>
								</div><!-- .card-body -->
							</div>
						</div>
					</div>





					<div class="row mt-3 mt-lg-4">
						<!-- products table -->
						<div class="col-12 col-lg-5 mt-2 mt-lg-0 pr-lg-2">
							<div class="bcard card h-100 pt-1 pt-lg-2">

								<div class="card-header brc-secondary-l3 pr-2 pt-2 pb-25">
									<h5 class="card-title text-dark-m2 pl-1 text-110">
										Popular Products
									</h5>

									<div class="card-toolbar align-self-center no-border">
										<span class="badge badge-pill bgc-secondary-l3 text-secondary-d3 mr-1">
											10+ more
										</span>
									</div>

									<div class="card-toolbar align-self-center">
										<!-- the dropdown used in some boxes (cards) -->
										<div class="dropdown dd-backdrop dd-backdrop-none-md">
											<button type="button"
												class="btn btn-sm btn-text-pale btn-brc-tp btn-outline-lightgrey btn-h-light-default btn-a-light-default dropdown-toggle"
												data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
												<i class="fa fa-ellipsis-h"></i>
											</button>

											<div
												class="dropdown-menu mw-auto dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md">
												<div class="dropdown-inner">
													<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
														href="#">
														<i class="fa fa-cog text-blue mr-1"></i>
														Some action
													</a>

													<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
														href="#">
														<i class="fas fa-tools text-blue mr-1"></i>
														Another action
													</a>
												</div>
											</div>
										</div>
									</div>
								</div>


								<div class="card-body p-0">
									<div class="table-responsive-md">

										<table class="table table-striped-primary table-borderless border-0 mb-0">
											<tbody>
												<tr></tr><!-- empty tr, so table-striped color starts from second row -->

												<tr>
													<td class="text-secondary pl-2 pl-lg-4 w-3 py-lg-3">
														<img alt="Wireless Headphone's image" src="assets/image/product/wireless-headphone.jpg"
															class="radius-1 mr-2 w-6" />
													</td>

													<td class="font-bolder py-lg-3">
														<span class="text-dark-m2 text-90">
															<a href="#" class="text-95 btn-text-dark btn-h-text-pink no-underline">
																Wireless Headphone
															</a>
														</span>
														<br />
														<span class="text-secondary-m2 text-80">
															Bluetooth Devices
														</span>
													</td>

													<td class="text-80 py-lg-3">
														<i class="fa fa-star text-orange-m2"></i>
														<i class="fa fa-star text-orange-m2"></i>
														<i class="fa fa-star text-orange-m2"></i>
													</td>

													<td class="font-bolder text-80 text-grey-d2 text-right pr-2 pr-lg-3 py-lg-3">
														$126.00
													</td>
												</tr>
												<tr>
													<td class="text-secondary pl-2 pl-lg-4 w-3 py-lg-3">
														<img alt="Running Shoes's image" src="assets/image/product/running-shoes.jpg"
															class="radius-1 mr-2 w-6" />
													</td>

													<td class="font-bolder py-lg-3">
														<span class="text-dark-m2 text-90">
															<a href="#" class="text-95 btn-text-dark btn-h-text-pink no-underline">
																Running Shoes
															</a>
														</span>
														<br />
														<span class="text-secondary-m2 text-80">
															Sportswear
														</span>
													</td>

													<td class="text-80 py-lg-3">
														<i class="fa fa-star text-orange-m2"></i>
														<i class="fa fa-star text-orange-m2"></i>
														<i class="fa fa-star text-orange-m2"></i>
													</td>

													<td class="font-bolder text-80 text-grey-d2 text-right pr-2 pr-lg-3 py-lg-3">
														$75.00
													</td>
												</tr>
												<tr>
													<td class="text-secondary pl-2 pl-lg-4 w-3 py-lg-3">
														<img alt="DSLR Camera's image" src="assets/image/product/camera.jpg"
															class="radius-1 mr-2 w-6" />
													</td>

													<td class="font-bolder py-lg-3">
														<span class="text-dark-m2 text-90">
															<a href="#" class="text-95 btn-text-dark btn-h-text-pink no-underline">
																DSLR Camera
															</a>
														</span>
														<br />
														<span class="text-secondary-m2 text-80">
															Photography
														</span>
													</td>

													<td class="text-80 py-lg-3">
														<i class="fa fa-star text-orange-m2"></i>
														<i class="fa fa-star text-orange-m2"></i>
														<i class="fa fa-star text-orange-m2"></i>
													</td>

													<td class="font-bolder text-80 text-grey-d2 text-right pr-2 pr-lg-3 py-lg-3">
														$990.00
													</td>
												</tr>
												<tr>
													<td class="text-secondary pl-2 pl-lg-4 w-3 py-lg-3">
														<img alt="Smart Speaker's image" src="assets/image/product/smart-speaker.jpg"
															class="radius-1 mr-2 w-6" />
													</td>

													<td class="font-bolder py-lg-3">
														<span class="text-dark-m2 text-90">
															<a href="#" class="text-95 btn-text-dark btn-h-text-pink no-underline">
																Smart Speaker
															</a>
														</span>
														<br />
														<span class="text-secondary-m2 text-80">
															Digital Assistants
														</span>
													</td>

													<td class="text-80 py-lg-3">
														<i class="fa fa-star text-orange-m2"></i>
														<i class="fa fa-star text-orange-m2"></i>
														<i class="fa fa-star text-grey-l1"></i>
													</td>

													<td class="font-bolder text-80 text-grey-d2 text-right pr-2 pr-lg-3 py-lg-3">
														$249.99
													</td>
												</tr>

											</tbody>
										</table>

									</div><!-- /.table-responsive-md -->
								</div><!-- /.card-body -->

							</div>
						</div>

						<!-- members table -->
						<div class="col-12 col-lg-7 mt-3 mt-lg-0">
							<div class="bcard card h-100 pt-1 pt-lg-2">

								<div class="card-header brc-secondary-l3 pos-rel">
									<h5 class="card-title text-dark-m2 pl-1 text-110">
										Top Members
									</h5>

									<div class="card-toolbar align-self-center no-border">
										<div data-toggle="buttons" class="btn-group btn-group-toggle">
											<label
												class="mr-2px btn btn-xs btn-bold border-0 btn-outline-lightgrey btn-h-light-secondary btn-a-light-blue radius-1 active">
												<input name="members" type="radio" value="1" checked="" />
												1W
											</label>

											<label
												class="mr-2px btn btn-xs btn-bold border-0 btn-outline-lightgrey btn-h-light-secondary btn-a-light-blue radius-1">
												<input name="members" type="radio" value="2" />
												1M
											</label>

											<label
												class="btn btn-xs btn-bold border-0 btn-outline-lightgrey btn-h-light-secondary btn-a-light-blue radius-1">
												<input name="members" type="radio" value="3" />
												1Y
											</label>
										</div>
									</div>

									<div class="card-toolbar align-self-center">
										<a href="#" data-action="reload" class="card-toolbar-btn text-success text-105"><i
												class="fas fa-sync-alt"></i></a>
									</div>
								</div>

								<div class="card-body p-0 mt-n1px">
									<div class="table-responsive-md">
										<table class="table table-border-x brc-secondary-l4 border-0 mb-0">
											<tbody>

												<tr class="bgc-h-default-l4">
													<td class="pl-2 pl-lg-4">
														<img alt="Emma Green's avatar" src="assets/image/avatar/avatar4.jpg"
															class="radius-round mr-2 w-5 h-5" />

														<a href="#" class="text-95 btn-text-dark btn-h-text-pink">
															Emma Green
														</a>
													</td>

													<td class="text-secondary-d2 text-90">
														Customer
													</td>

													<td class=" text-dark-m3 text-85">
														Dubai
													</td>

													<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
														<span class="d-inline-block bgc-green radius-1 p-1 align-middle mr-1"></span>
														<span>Approved</span>


													</td>

													<td>
														<div class="dropdown dd-backdrop dd-backdrop-none-md d-inline-block">
															<button type="button"
																class="btn btn-bgc-tp btn-outline-light btn-h-default btn-a-default btn-bold btn-xs border-0 dropdown-toggle"
																data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
																<i class="fa fa-cog text-95"></i>
															</button>

															<div
																class="dropdown-menu dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md mw-auto">
																<div class="dropdown-inner">
																	<div
																		class="d-md-none dropdown-header border-b-1 brc-secondary-l2 text-600 text-110 text-center">
																		<img alt="Emma Green's avatar" src="assets/image/avatar/avatar4.jpg"
																			class="radius-round mr-2 w-5 h-5" />
																		Emma Green
																	</div>

																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-check mr-1 text-success w-2"></i>
																		Approve
																	</a>
																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-times text-danger mr-1 w-2"></i>
																		Reject
																	</a>
																</div>
															</div>
														</div>
													</td>
												</tr>
												<tr class="bgc-h-default-l4">
													<td class="pl-2 pl-lg-4">
														<img alt="Bob Doe's avatar" src="assets/image/avatar/avatar1.jpg"
															class="radius-round mr-2 w-5 h-5" />

														<a href="#" class="text-95 btn-text-dark btn-h-text-pink">
															Bob Doe
														</a>
													</td>

													<td class="text-secondary-d2 text-90">
														Moderator
													</td>

													<td class=" text-dark-m3 text-85">
														London
													</td>

													<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
														<span class="d-inline-block bgc-green radius-1 p-1 align-middle mr-1"></span>
														<span>Approved</span>


													</td>

													<td>
														<div class="dropdown dd-backdrop dd-backdrop-none-md d-inline-block">
															<button type="button"
																class="btn btn-bgc-tp btn-outline-light btn-h-default btn-a-default btn-bold btn-xs border-0 dropdown-toggle"
																data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
																<i class="fa fa-cog text-95"></i>
															</button>

															<div
																class="dropdown-menu dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md mw-auto">
																<div class="dropdown-inner">
																	<div
																		class="d-md-none dropdown-header border-b-1 brc-secondary-l2 text-600 text-110 text-center">
																		<img alt="Bob Doe's avatar" src="assets/image/avatar/avatar1.jpg"
																			class="radius-round mr-2 w-5 h-5" />
																		Bob Doe
																	</div>

																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-check mr-1 text-success w-2"></i>
																		Approve
																	</a>
																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-times text-danger mr-1 w-2"></i>
																		Reject
																	</a>
																</div>
															</div>
														</div>
													</td>
												</tr>
												<tr class="bgc-h-default-l4">
													<td class="pl-2 pl-lg-4">
														<span
															class="d-inline-block w-5 h-5 text-center mr-2 pt-15 radius-round bgc-primary-l3 text-primary-d2 border-2 brc-white font-bolder text-90">
															<span>JS</span>
														</span>

														<a href="#" class="text-95 btn-text-dark btn-h-text-pink">
															John Smith
														</a>
													</td>

													<td class="text-secondary-d2 text-90">
														Customer
													</td>

													<td class=" text-dark-m3 text-85">
														Istanbul
													</td>

													<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">

														<span class="d-inline-block bgc-warning radius-1 p-1 align-middle mr-1"></span>
														<span>Pending</span>

													</td>

													<td>
														<div class="dropdown dd-backdrop dd-backdrop-none-md d-inline-block">
															<button type="button"
																class="btn btn-bgc-tp btn-outline-light btn-h-default btn-a-default btn-bold btn-xs border-0 dropdown-toggle"
																data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
																<i class="fa fa-cog text-95"></i>
															</button>

															<div
																class="dropdown-menu dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md mw-auto">
																<div class="dropdown-inner">
																	<div
																		class="d-md-none dropdown-header border-b-1 brc-secondary-l2 text-600 text-110 text-center">
																		John Smith
																	</div>

																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-check mr-1 text-success w-2"></i>
																		Approve
																	</a>
																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-times text-danger mr-1 w-2"></i>
																		Reject
																	</a>
																</div>
															</div>
														</div>
													</td>
												</tr>
												<tr class="bgc-h-default-l4">
													<td class="pl-2 pl-lg-4">
														<img alt="Jim Jackson's avatar" src="assets/image/avatar/avatar6.jpg"
															class="radius-round mr-2 w-5 h-5" />

														<a href="#" class="text-95 btn-text-dark btn-h-text-pink">
															Jim Jackson
														</a>
													</td>

													<td class="text-secondary-d2 text-90">
														Tester
													</td>

													<td class=" text-dark-m3 text-85">
														Unknown
													</td>

													<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">


														<span class="d-inline-block bgc-red radius-1 p-1 align-middle mr-1"></span>
														<span>Banned</span>
													</td>

													<td>
														<div class="dropdown dd-backdrop dd-backdrop-none-md d-inline-block">
															<button type="button"
																class="btn btn-bgc-tp btn-outline-light btn-h-default btn-a-default btn-bold btn-xs border-0 dropdown-toggle"
																data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
																<i class="fa fa-cog text-95"></i>
															</button>

															<div
																class="dropdown-menu dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md mw-auto">
																<div class="dropdown-inner">
																	<div
																		class="d-md-none dropdown-header border-b-1 brc-secondary-l2 text-600 text-110 text-center">
																		<img alt="Jim Jackson's avatar" src="assets/image/avatar/avatar6.jpg"
																			class="radius-round mr-2 w-5 h-5" />
																		Jim Jackson
																	</div>

																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-check mr-1 text-success w-2"></i>
																		Approve
																	</a>
																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-times text-danger mr-1 w-2"></i>
																		Reject
																	</a>
																</div>
															</div>
														</div>
													</td>
												</tr>
												<tr class="bgc-h-default-l4">
													<td class="pl-2 pl-lg-4">
														<img alt="Susan Wise's avatar" src="assets/image/avatar/avatar5.jpg"
															class="radius-round mr-2 w-5 h-5" />

														<a href="#" class="text-95 btn-text-dark btn-h-text-pink">
															Susan Wise
														</a>
													</td>

													<td class="text-secondary-d2 text-90">
														Customer
													</td>

													<td class=" text-dark-m3 text-85">
														Los Angeles
													</td>

													<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
														<span class="d-inline-block bgc-green radius-1 p-1 align-middle mr-1"></span>
														<span>Approved</span>


													</td>

													<td>
														<div class="dropdown dd-backdrop dd-backdrop-none-md d-inline-block">
															<button type="button"
																class="btn btn-bgc-tp btn-outline-light btn-h-default btn-a-default btn-bold btn-xs border-0 dropdown-toggle"
																data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
																<i class="fa fa-cog text-95"></i>
															</button>

															<div
																class="dropdown-menu dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md mw-auto">
																<div class="dropdown-inner">
																	<div
																		class="d-md-none dropdown-header border-b-1 brc-secondary-l2 text-600 text-110 text-center">
																		<img alt="Susan Wise's avatar" src="assets/image/avatar/avatar5.jpg"
																			class="radius-round mr-2 w-5 h-5" />
																		Susan Wise
																	</div>

																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-check mr-1 text-success w-2"></i>
																		Approve
																	</a>
																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-times text-danger mr-1 w-2"></i>
																		Reject
																	</a>
																</div>
															</div>
														</div>
													</td>
												</tr>


											</tbody>
										</table>
									</div><!-- /.table-responsive-md -->
								</div><!-- /.card-body -->

							</div>
						</div>
					</div>





					<div class="row mt-4">
						<!-- demographics chart -->
						<div class="col-12 col-lg-4 pr-lg-2">
							<div class="bcard card h-100 pt-0 mx-auto">
								<div class="card-header border-0">
									<h5 class="card-title text-120 text-dark-m1 pl-1">
										Demographics
										<span class="text-dark-l2 text-80 d-block">
											Last 7 days
										</span>
									</h5>

									<div class="card-toolbar no-border align-self-start">
										<!-- the dropdown used in some boxes (cards) -->
										<div class="dropdown dd-backdrop dd-backdrop-none-md">
											<button type="button"
												class="btn btn-sm btn-text-pale btn-brc-tp btn-outline-lightgrey btn-h-light-default btn-a-light-default dropdown-toggle"
												data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
												<i class="fa fa-ellipsis-h"></i>
											</button>

											<div
												class="dropdown-menu mw-auto dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md">
												<div class="dropdown-inner">
													<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
														href="#">
														<i class="fa fa-cog text-blue mr-1"></i>
														Some action
													</a>

													<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
														href="#">
														<i class="fas fa-tools text-blue mr-1"></i>
														Another action
													</a>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="card-body p-3 h-100 d-flex align-items-center">
									<div class="col-12 mx-auto">
										<canvas id="demographics-chart" height="110"></canvas>
									</div>
								</div><!-- .card-body -->
							</div>
						</div>




						<!-- traffic sources chart -->
						<div class="col-12 col-lg-4 mt-2 mt-lg-0 pr-lg-2">
							<div class="bcard card h-100 pt-0 mx-auto">

								<div class="card-header border-0">
									<h5 class="card-title text-120 text-dark-m1 pl-1">
										Traffic Sources
										<span class="text-dark-l2 text-80 d-block">
											Last 7 days
										</span>
									</h5>

									<div class="card-toolbar no-border align-self-start">
										<!-- the dropdown used in some boxes (cards) -->
										<div class="dropdown dd-backdrop dd-backdrop-none-md">
											<button type="button"
												class="btn btn-sm btn-text-pale btn-brc-tp btn-outline-lightgrey btn-h-light-default btn-a-light-default dropdown-toggle"
												data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
												<i class="fa fa-ellipsis-h"></i>
											</button>

											<div
												class="dropdown-menu mw-auto dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md">
												<div class="dropdown-inner">
													<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
														href="#">
														<i class="fa fa-cog text-blue mr-1"></i>
														Some action
													</a>

													<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
														href="#">
														<i class="fas fa-tools text-blue mr-1"></i>
														Another action
													</a>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="card-body p-3">
									<div class="d-flex align-items-start justify-content-center flex-wrap">
										<div class="pos-rel">
											<div class="position-center text-center text-default-d2 mt-n25">
												<span>
													total
												</span>
												<span class="mt-n2 d-block text-200 text-secondary-d3 text-600">
													55k
												</span>
											</div>

											<canvas id="traffic-source-chart" class="pos-rel mb-3"
												style="height: 150px; width: 150px; max-height: 150px; max-width: 150px;"></canvas>
										</div>
									</div>
								</div><!-- .card-body -->

							</div>
						</div>




						<!-- user devices chart -->
						<div class="col-12 col-lg-4 mt-2 mt-lg-0">
							<div class="bcard card h-100 pt-0 mx-auto">

								<div class="card-header border-0">
									<h5 class="card-title text-120 text-dark-m1 pl-1">
										User Devices
										<span class="text-dark-l2 text-80 d-block">
											Last 7 days
										</span>
									</h5>

									<div class="card-toolbar no-border align-self-start">
										<!-- the dropdown used in some boxes (cards) -->
										<div class="dropdown dd-backdrop dd-backdrop-none-md">
											<button type="button"
												class="btn btn-sm btn-text-pale btn-brc-tp btn-outline-lightgrey btn-h-light-default btn-a-light-default dropdown-toggle"
												data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
												<i class="fa fa-ellipsis-h"></i>
											</button>

											<div
												class="dropdown-menu mw-auto dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md">
												<div class="dropdown-inner">
													<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
														href="#">
														<i class="fa fa-cog text-blue mr-1"></i>
														Some action
													</a>

													<a class="dropdown-item btn btn-light-grey btn-h-lighter-default btn-a-lighter-primary btn-bgc-tp px-3"
														href="#">
														<i class="fas fa-tools text-blue mr-1"></i>
														Another action
													</a>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="card-body p-3">
									<div class="d-flex align-items-start justify-content-center flex-wrap">
										<div class="pos-rel">
											<div class="position-center text-center text-default-d2 mt-n25">
												<span>
													total
												</span>
												<span class="mt-n2 d-block text-200 text-secondary-d3 text-600">
													62k
												</span>
											</div>

											<canvas id="user-device-chart" class="pos-rel mb-3"
												style="height: 150px; width: 150px; max-height: 150px; max-width: 150px;"></canvas>
										</div>
									</div>
								</div><!-- .card-body -->

							</div>
						</div>
					</div><!-- /.row -->





					<div class="row mt-4">
						<!-- orders table -->
						<div class="col-12 col-lg-9">
							<div class="bcard card h-100 pt-1 pt-lg-2">

								<div class="card-header border-0">
									<h5 class="card-title text-dark-m2 pl-1 text-110">
										Recent Orders
									</h5>

									<div class="card-toolbar align-self-center no-border">
										<div class="dropdown dd-backdrop dd-backdrop-none-md">
											<a class="btn btn-light-green text-600 btn-xs mr-1" href="#" role="button" data-toggle="dropdown"
												data-display="static" aria-haspopup="true" aria-expanded="false">
												Export
												<i class="fa fa-download ml-1 text-90"></i>
											</a>
											<div
												class="dropdown-menu dropdown-menu-right dropdown-caret dropdown-animated dd-slide-up dd-slide-none-md mw-auto">
												<div class="dropdown-inner">
													<div class="d-md-none dropdown-header border-b-1 brc-secondary-l1 text-600 text-110">
														Export to:
													</div>
													<a class="dropdown-item btn btn-bold btn-outline-lightgrey btn-h-light-default btn-a-light-default mx-1"
														href="#">PDF</a>
													<a class="dropdown-item btn btn-bold btn-outline-lightgrey btn-h-light-default btn-a-light-default mx-1"
														href="#">Excel</a>
													<a class="dropdown-item btn btn-bold btn-outline-lightgrey btn-h-light-default btn-a-light-default mx-1"
														href="#">CSV</a>
												</div>
											</div>
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
														ID
													</th>

													<th>
														User
													</th>

													<th>
														Date
													</th>

													<th>
														Address
													</th>

													<th>
														Status
													</th>

													<th>
														Total
													</th>
												</tr>
											</thead>

											<tbody>

												<tr class="bgc-h-secondary-l4">
													<td class="pl-2 pl-lg-4">
														<a href="#" class="text-95 text-primary text-600">
															#6328
														</a>
													</td>

													<td class="text-dark-m2 text-85">
														Bob Doe
													</td>

													<td class=" text-dark-m2 text-85">
														May 8th
													</td>

													<td class=" text-dark-m2 text-85">
														London
													</td>

													<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">
														<span class="d-inline-block bgc-green radius-2px p-1 align-middle mr-1"></span>
														<span>Processed</span>



													</td>

													<td class="text-secondary-d3 text-95">
														$149,98
														<div class="float-right dropdown dd-backdrop dd-backdrop-none-md">
															<button type="button"
																class="btn btn-text-pale btn-bgc-tp btn-light-lightgrey btn-h-default btn-a-default border-0 px-25 dropdown-toggle"
																data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
																<i class="fa fa-ellipsis-v text-95"></i>
															</button>
															<div
																class="dropdown-menu dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md mw-auto">
																<div class="dropdown-inner">
																	<div
																		class="d-md-none dropdown-header border-b-1 brc-secondary-l1 text-600 text-110 text-center">
																		Order #6328
																	</div>

																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-arrow-right mr-2px text-success w-2"></i>
																		Proceed
																	</a>

																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-undo text-danger mr-2px w-2"></i>
																		Cancel
																	</a>
																</div>
															</div>
														</div>
													</td>
												</tr>
												<tr class="bgc-h-secondary-l4">
													<td class="pl-2 pl-lg-4">
														<a href="#" class="text-95 text-primary text-600">
															#7193
														</a>
													</td>

													<td class="text-dark-m2 text-85">
														Maria Doe
													</td>

													<td class=" text-dark-m2 text-85">
														May 6th
													</td>

													<td class=" text-dark-m2 text-85">
														Dubai
													</td>

													<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">



														<span class="d-inline-block bgc-red radius-2px p-1 align-middle mr-1"></span>
														<span>Cancelled</span>
													</td>

													<td class="text-secondary-d3 text-95">
														$29,45
														<div class="float-right dropdown dd-backdrop dd-backdrop-none-md">
															<button type="button"
																class="btn btn-text-pale btn-bgc-tp btn-light-lightgrey btn-h-default btn-a-default border-0 px-25 dropdown-toggle"
																data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
																<i class="fa fa-ellipsis-v text-95"></i>
															</button>
															<div
																class="dropdown-menu dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md mw-auto">
																<div class="dropdown-inner">
																	<div
																		class="d-md-none dropdown-header border-b-1 brc-secondary-l1 text-600 text-110 text-center">
																		Order #7193
																	</div>

																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-arrow-right mr-2px text-success w-2"></i>
																		Proceed
																	</a>

																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-undo text-danger mr-2px w-2"></i>
																		Cancel
																	</a>
																</div>
															</div>
														</div>
													</td>
												</tr>
												<tr class="bgc-h-secondary-l4">
													<td class="pl-2 pl-lg-4">
														<a href="#" class="text-95 text-primary text-600">
															#8481
														</a>
													</td>

													<td class="text-dark-m2 text-85">
														John Smith
													</td>

													<td class=" text-dark-m2 text-85">
														May 5th
													</td>

													<td class=" text-dark-m2 text-85">
														Istanbul
													</td>

													<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">


														<span class="d-inline-block bgc-warning radius-2px p-1 align-middle mr-1"></span>
														<span>Unpaid</span>

													</td>

													<td class="text-secondary-d3 text-95">
														$65,99
														<div class="float-right dropdown dd-backdrop dd-backdrop-none-md">
															<button type="button"
																class="btn btn-text-pale btn-bgc-tp btn-light-lightgrey btn-h-default btn-a-default border-0 px-25 dropdown-toggle"
																data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
																<i class="fa fa-ellipsis-v text-95"></i>
															</button>
															<div
																class="dropdown-menu dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md mw-auto">
																<div class="dropdown-inner">
																	<div
																		class="d-md-none dropdown-header border-b-1 brc-secondary-l1 text-600 text-110 text-center">
																		Order #8481
																	</div>

																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-arrow-right mr-2px text-success w-2"></i>
																		Proceed
																	</a>

																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-undo text-danger mr-2px w-2"></i>
																		Cancel
																	</a>
																</div>
															</div>
														</div>
													</td>
												</tr>
												<tr class="bgc-h-secondary-l4">
													<td class="pl-2 pl-lg-4">
														<a href="#" class="text-95 text-primary text-600">
															#8229
														</a>
													</td>

													<td class="text-dark-m2 text-85">
														Jim Doe
													</td>

													<td class=" text-dark-m2 text-85">
														May 3rd
													</td>

													<td class=" text-dark-m2 text-85">
														Unknown
													</td>

													<td class="font-bolder text-75 text-dark-m2 pr-2 pr-lg-3">

														<span class="d-inline-block bgc-blue radius-2px p-1 align-middle mr-1"></span>
														<span>Delivered</span>


													</td>

													<td class="text-secondary-d3 text-95">
														$350,00
														<div class="float-right dropdown dd-backdrop dd-backdrop-none-md">
															<button type="button"
																class="btn btn-text-pale btn-bgc-tp btn-light-lightgrey btn-h-default btn-a-default border-0 px-25 dropdown-toggle"
																data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
																<i class="fa fa-ellipsis-v text-95"></i>
															</button>
															<div
																class="dropdown-menu dropdown-menu-right dropdown-caret dropdown-animated dd-appear-center dd-slide-none-md mw-auto">
																<div class="dropdown-inner">
																	<div
																		class="d-md-none dropdown-header border-b-1 brc-secondary-l1 text-600 text-110 text-center">
																		Order #8229
																	</div>

																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-arrow-right mr-2px text-success w-2"></i>
																		Proceed
																	</a>

																	<a class="dropdown-item px-3" href="#">
																		<i class="fa fa-undo text-danger mr-2px w-2"></i>
																		Cancel
																	</a>
																</div>
															</div>
														</div>
													</td>
												</tr>

											</tbody>
										</table>
									</div><!-- /.table-responsive-md -->

								</div><!-- /.card-body -->
							</div>
						</div>

						<div class="col-12 col-lg-3 mt-2 mt-lg-0 pl-lg-2">
							<div class="h-100 row mt-sm-3 mt-lg-0">

								<div class="col-12 col-sm-4 col-lg-12">
									<div class="bcard h-100 flex-grow-1 p-3 pt-1 pt-lg-2">
										<h6 class="text-dark-m2 text-90 text-600">
											<span class="d-inline-block p-15 radius-2 bgc-green-tp1 mr-1"></span>
											Total Orders
										</h6>

										<div class="text-primary-m1 text-center text-200">
											<span class="text-120">
												32,809
											</span>
										</div>
									</div>
								</div>

								<div class="col-12 col-sm-4 col-lg-12 py-2 py-sm-0 py-lg-2">
									<div class="bcard h-100 flex-grow-1 p-3 pt-1 pt-lg-2">
										<h6 class="text-dark-m2 text-90 text-600">
											<span class="d-inline-block p-15 radius-2 bgc-purple-tp1 mr-1"></span>
											Daily Average
										</h6>

										<div class="text-primary-m1 text-center text-200">
											<span class="text-120">
												285
											</span>
										</div>
									</div>
								</div>

								<div class="col-12 col-sm-4 col-lg-12">
									<div class="bcard h-100 flex-grow-1 p-3 pt-1 pt-lg-2">
										<h6 class="text-dark-m2 text-90 text-600">
											<span class="d-inline-block p-15 radius-2 bgc-brown-tp1 mr-1"></span>
											Average Amount
										</h6>

										<div class="text-primary-m1 text-center text-200">
											<span class="text-120">
												$141
											</span>
										</div>
									</div>
								</div>

							</div>
						</div>
					</div>




					<div class="row mt-4 pt-1">
						<div class="col-12 col-lg-4 mt-lg-0 pr-lg-2">
							<div class="bcard card pt-1 pt-lg-2 h-100">
								<div class="card-header bg-transparent brc-default-l3">
									<h5 class="card-title text-dark-m2 pl-1 text-110">
										Latest Activity
									</h5>

									<div class="card-toolbar align-self-center no-border">
										<a href="#"
											class="btn btn-xs btn-lighter-default btn-h-light-warning btn-a-light-warning text-600 px-3 radius-round">
											See all
										</a>
									</div>

									<div class="card-toolbar align-self-center">
										<a href="#" data-action="reload" class="card-toolbar-btn text-blue text-110">
											<i class="fa fa-sync-alt"></i>
										</a>
										<a href="#" data-action="close" class="card-toolbar-btn text-danger-m1 text-110 ml-25">
											<i class="fa fa-times"></i>
										</a>
									</div>
								</div>

								<div class="card-body p-3 border-0">
									<div class="text-grey-m1 text-95">
										<div class="d-flex">
											<div class="minh-100 pos-rel">
												<!-- the vertical line connecting activity icons -->
												<div class="position-tl ml-4 h-100 border-l-3 brc-secondary-l1"></div>

												<!-- the icon with white border to cover the above line -->
												<div
													class="pos-rel text-center pt-2 mx-2px w-6 h-6 border-4 brc-white radius-round bgc-purple text-white">
													<i class='fa fa-camera-retro'></i>
												</div>
											</div>

											<div class="mx-2 flex-grow-1">
												<div>
													<a href="#" class="font-bolder btn-text-dark btn-h-text-danger">
														Susan Dalek
													</a>
													<div class="text-85 text-secondary ml-auto text-nowrap">
														3 min ago
													</div>
												</div>

												<div class="text-grey-d3 text-95 mt-15 pos-rel">
													Uploaded a new photo
												</div>

												<hr class="brc-secondary-l3" />
											</div>
										</div>
									</div>
									<div class="text-grey-m1 text-95">
										<div class="d-flex">
											<div class="minh-100 pos-rel">
												<!-- the vertical line connecting activity icons -->
												<div class="position-tl ml-4 h-100 border-l-3 brc-secondary-l1"></div>

												<!-- the icon with white border to cover the above line -->
												<div
													class="pos-rel text-center pt-2 mx-2px w-6 h-6 border-4 brc-white radius-round bgc-green text-white">
													<i class='fa fa-check-double'></i>
												</div>
											</div>

											<div class="mx-2 flex-grow-1">
												<div>
													<a href="#" class="font-bolder btn-text-dark btn-h-text-danger">
														Robin Hood
													</a>
													<div class="text-85 text-secondary ml-auto text-nowrap">
														4 min ago
													</div>
												</div>

												<div class="text-grey-d3 text-95 mt-15 pos-rel">
													Upgraded his membership plan
												</div>

												<hr class="brc-secondary-l3" />
											</div>
										</div>
									</div>
									<div class="text-grey-m1 text-95">
										<div class="d-flex">
											<div class="minh-100 pos-rel">
												<!-- the vertical line connecting activity icons -->
												<div class="position-tl ml-4 h-100 border-l-3 brc-secondary-l1"></div>

												<!-- the icon with white border to cover the above line -->
												<div
													class="pos-rel text-center pt-2 mx-2px w-6 h-6 border-4 brc-white radius-round bgc-blue text-white">
													<i class='fa fa-shopping-cart'></i>
												</div>
											</div>

											<div class="mx-2 flex-grow-1">
												<div>
													<a href="#" class="font-bolder btn-text-dark btn-h-text-danger">
														River Song
													</a>
													<div class="text-85 text-secondary ml-auto text-nowrap">
														11 min ago
													</div>
												</div>

												<div class="text-grey-d3 text-95 mt-15 pos-rel">
													Reviewed a product
												</div>

												<hr class="brc-secondary-l3" />
											</div>
										</div>
									</div>
									<div class="text-grey-m1 text-95">
										<div class="d-flex">
											<div class="minh-100 pos-rel">
												<!-- the vertical line connecting activity icons -->


												<!-- the icon with white border to cover the above line -->
												<div
													class="pos-rel text-center pt-2 mx-2px w-6 h-6 border-4 brc-white radius-round bgc-warning text-white">
													<i class='fa fa-trophy'></i>
												</div>
											</div>

											<div class="mx-2 flex-grow-1">
												<div>
													<a href="#" class="font-bolder btn-text-dark btn-h-text-danger">
														Jack Alien
													</a>
													<div class="text-85 text-secondary ml-auto text-nowrap">
														20 min ago
													</div>
												</div>

												<div class="text-grey-d3 text-95 mt-15 pos-rel">
													Won a trophy
												</div>


											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="col-12 col-lg-4 mt-3 mt-lg-0 pr-lg-2">
							<div class="bcard card h-100 pt-1 pt-lg-2 h-100">

								<div class="card-header brc-default-l3">
									<h5 class="card-title text-dark-m2 pl-1 text-110">
										Open Tasks
									</h5>

									<div class="card-toolbar align-self-center no-border">
										<a href="#"
											class="btn btn-xs btn-lighter-default btn-h-light-warning btn-a-light-warning text-600 px-3 radius-round">
											Select all
										</a>
									</div>

									<div class="card-toolbar align-self-center">
										<a href="#" data-action="close" class="card-toolbar-btn text-danger-m1 text-110">
											<i class="fa fa-times"></i>
										</a>
									</div>
								</div>

								<div class="card-body p-0">
									<form autocomplete="off" class="pos-rel px-3 py-2" id="tasks">
										<div class="text-grey-m1 text-95  border-b-1 brc-secondary-l3">
											<div class="d-flex py-2 my-2 pos-rel">
												<div class="d-none h-75 position-lc pl-3px radius-round bgc-blue-m1"></div>

												<div class="pl-1">
													<label class="brc-dark-l1 mb-0 d-flex align-items-center">
														<input onmouseenter="this.indeterminate=true" type="checkbox"
															class="position-lc radius-round input-lg text-90 text-blue brc-inherit brc-on-checked brc-dark-m4 brc-h-warning-m1 border-2" />
														<span class="ml-45">
															<span class="task-title text-600 text-95 btn-text-dark btn-h-text-primary">
																Process emails and social media
															</span>
															<br />
															<span class="text-dark-m3 text-90">
																<i class="far fa-bell text-dark-l1 mr-1"></i>
																delay until next week
															</span>
														</span>
													</label>
												</div>

												<div class="ml-auto">
													<span class='badge bgc-green-l3 text-green-d1 border-l-2 brc-green-m3'>open</span>
												</div>
											</div>
										</div>
										<div class="text-grey-m1 text-95  border-b-1 brc-secondary-l3">
											<div class="d-flex py-2 my-2 pos-rel">
												<div class="d-none h-75 position-lc pl-3px radius-round bgc-green-m1"></div>

												<div class="pl-1">
													<label class="brc-dark-l1 mb-0 d-flex align-items-center">
														<input onmouseenter="this.indeterminate=true" type="checkbox"
															class="position-lc radius-round input-lg text-90 text-blue brc-inherit brc-on-checked brc-dark-m4 brc-h-warning-m1 border-2" />
														<span class="ml-45">
															<span class="task-title text-600 text-95 btn-text-dark btn-h-text-primary">
																Design business card
															</span>
															<br />
															<span class="text-dark-m3 text-90">
																<i class="far fa-bell text-dark-l1 mr-1"></i>
																choose a logo
															</span>
														</span>
													</label>
												</div>

												<div class="ml-auto">
													<span class='badge bgc-green-l3 text-green-d1 border-l-2 brc-green-m3'>open</span>
												</div>
											</div>
										</div>
										<div class="text-grey-m1 text-95  border-b-1 brc-secondary-l3">
											<div class="d-flex py-2 my-2 pos-rel">
												<div class="d-none h-75 position-lc pl-3px radius-round bgc-warning-m1"></div>

												<div class="pl-1">
													<label class="brc-dark-l1 mb-0 d-flex align-items-center">
														<input onmouseenter="this.indeterminate=true" type="checkbox"
															class="position-lc radius-round input-lg text-90 text-blue brc-inherit brc-on-checked brc-dark-m4 brc-h-warning-m1 border-2" />
														<span class="ml-45">
															<span class="task-title text-600 text-95 btn-text-dark btn-h-text-primary">
																Meeting with Alex
															</span>
															<br />
															<span class="text-dark-m3 text-90">
																<i class="far fa-bell text-dark-l1 mr-1"></i>
																location not specified <i
																	class='fa fa-exclamation-circle text-danger-m1 text-110 mx-1'></i>
															</span>
														</span>
													</label>
												</div>

												<div class="ml-auto">
													<span class='badge bgc-orange-l3 text-brown-d1 border-l-2 brc-orange-m3'>cancelled</span>
												</div>
											</div>
										</div>
										<div class="text-grey-m1 text-95  border-b-1 brc-secondary-l3">
											<div class="d-flex py-2 my-2 pos-rel">
												<div class="d-none h-75 position-lc pl-3px radius-round bgc-purple-m1"></div>

												<div class="pl-1">
													<label class="brc-dark-l1 mb-0 d-flex align-items-center">
														<input onmouseenter="this.indeterminate=true" type="checkbox"
															class="position-lc radius-round input-lg text-90 text-blue brc-inherit brc-on-checked brc-dark-m4 brc-h-warning-m1 border-2" />
														<span class="ml-45">
															<span class="task-title text-600 text-95 btn-text-dark btn-h-text-primary">
																Order books
															</span>
															<br />
															<span class="text-dark-m3 text-90">
																<i class="far fa-bell text-dark-l1 mr-1"></i>
																preferrably e-books
															</span>
														</span>
													</label>
												</div>

												<div class="ml-auto">
													<span class='badge bgc-green-l3 text-green-d1 border-l-2 brc-green-m3'>open</span>
												</div>
											</div>
										</div>
										<div class="text-grey-m1 text-95 ">
											<div class="d-flex py-2 my-2 pos-rel">
												<div class="d-none h-75 position-lc pl-3px radius-round bgc-red-m1"></div>

												<div class="pl-1">
													<label class="brc-dark-l1 mb-0 d-flex align-items-center">
														<input onmouseenter="this.indeterminate=true" type="checkbox"
															class="position-lc radius-round input-lg text-90 text-blue brc-inherit brc-on-checked brc-dark-m4 brc-h-warning-m1 border-2" />
														<span class="ml-45">
															<span class="task-title text-600 text-95 btn-text-dark btn-h-text-primary">
																Hire assistant
															</span>
															<br />
															<span class="text-dark-m3 text-90">
																<i class="far fa-bell text-dark-l1 mr-1"></i>
																A.S.A.P <span class='fa-stack w-auto mx-1'> <i
																		class='fas fa-circle text-dark fa-stack-1x text-110'></i> <i
																		class='fas fa-angry text-warning-m2 text-130 fa-stack-1x pos-rel'></i> </span>
															</span>
														</span>
													</label>
												</div>

												<div class="ml-auto">
													<span class='badge bgc-red-l3 text-danger-d1 border-l-2 brc-red-m4'>urgent</span>
												</div>
											</div>
										</div>
									</form>
								</div>

							</div>
						</div>

						<div class="col-12 col-lg-4 mt-3 mt-lg-0">
							<div class="bcard card pt-1 pt-lg-2 h-100" id="conversations">

								<div class="card-header bg-transparent brc-default-l3">
									<h5 class="card-title text-dark-m2 pl-1 text-110">
										Conversation
									</h5>

									<div class="card-toolbar align-self-center">
										<a href="#" data-action="expand" class="card-toolbar-btn text-orange-d2 text-110 d-style">
											<i class="fa fa-expand d-n-active"></i>
											<i class="fa fa-compress d-active"></i>
										</a>
										<a href="#" data-action="close" class="card-toolbar-btn text-danger-m1 text-110 ml-3">
											<i class="fa fa-times"></i>
										</a>
									</div>
								</div>


								<div class="card-body p-0 border-0">
									<div class="ace-scroll px-2" data-ace-scroll='{"height": 320, "smooth":true}'>

										<!-- if conversation item is on left -->
										<div class="d-flex px-3 mb-1">
											<div>
												<div class="mb-1 mt-4">
													<span class="pos-rel">
														<img alt="Jack's avatar" src="assets/image/avatar/avatar6.jpg"
															class="radius-round w-4 h-4" />
														<span
															class="radius-round position-tr bgc-success-d1 brc-white border-2 p-1 mt-n1"></span><!-- the small green dot -->
													</span>

													<span class="text-600 text-90 ml-1">
														<a href="#" class="btn-text-dark btn-h-text-primary">
															Jack
														</a>
													</span>

													<span class="text-80 text-grey-m2 ml-1">
														38 sec
													</span>
												</div>

												<div class="py-2 px-3 radius-1 bgc-grey-l4 text-dark-m1 border-1 brc-grey-l2">
													<div class="text-90">
														Raw denim you probably haven't heard of actively? <span class='fa-stack w-auto'> <i
																class='fas fa-circle text-dark fa-stack-1x text-100'></i> <i
																class='fas fa-smile-beam text-warning-m3 text-125 fa-stack-1x pos-rel'></i> </span>
													</div>
												</div>
											</div>
										</div>



										<!-- if conversation item is on left -->
										<div class="d-flex px-3 mb-1">
											<div>

												<div class="py-2 px-3 radius-1 bgc-grey-l4 text-dark-m1 border-1 brc-grey-l2">
													<div class="text-90">
														Lorem ipsum dolor sit amet
													</div>
												</div>
											</div>
										</div>




										<!-- if conversation item is on left -->
										<div class="d-flex flex-row-reverse px-3 text-right mb-1">
											<div>
												<div class="mb-1 mt-4">
													<span class="pos-rel">
														<img alt="Jane's avatar" src="assets/image/avatar/avatar5.jpg"
															class="radius-round w-4 h-4" />
														<span
															class="radius-round position-tr bgc-warning-d1 brc-white border-2 p-1 mt-n1"></span><!-- the small orange dot -->
													</span>

													<span class="text-600 text-90 ml-1">
														<a href="#" class="btn-text-dark btn-h-text-primary">
															Jane
														</a>
													</span>

													<span class="text-80 text-grey-m2 ml-1">
														2 min
													</span>
												</div>

												<div class="py-2 px-3 radius-1 bgc-green-d3 border-1 brc-black-tp11 text-white pos-rel">
													<!-- add this dark layer for a darker background -->
													<!-- <div class="position-tl w-100 h-100 bgc-black-tp10 radius-1"></div> -->
													<div class="text-90 pos-rel">
														Consectetur adipiscing elit. Quis nostrud exercitation!!!
													</div>
												</div>
											</div>
										</div>


										<!-- if conversation item is on left -->
										<div class="d-flex px-3 mb-1">
											<div>
												<div class="mb-1 mt-4">
													<span class="pos-rel">
														<img alt="Jack's avatar" src="assets/image/avatar/avatar6.jpg"
															class="radius-round w-4 h-4" />
														<span
															class="radius-round position-tr bgc-success-d1 brc-white border-2 p-1 mt-n1"></span><!-- the small green dot -->
													</span>

													<span class="text-600 text-90 ml-1">
														<a href="#" class="btn-text-dark btn-h-text-primary">
															Jack
														</a>
													</span>

													<span class="text-80 text-grey-m2 ml-1">
														3 min
													</span>
												</div>

												<div class="py-2 px-3 radius-1 bgc-grey-l4 text-dark-m1 border-1 brc-grey-l2">
													<div class="text-90">
														Heard of them jean shorts Austin!
													</div>
												</div>
											</div>
										</div>




										<!-- if conversation item is on left -->
										<div class="d-flex flex-row-reverse px-3 text-right mb-1">
											<div>
												<div class="mb-1 mt-4">
													<span class="pos-rel">
														<img alt="Jane's avatar" src="assets/image/avatar/avatar5.jpg"
															class="radius-round w-4 h-4" />
														<span
															class="radius-round position-tr bgc-warning-d1 brc-white border-2 p-1 mt-n1"></span><!-- the small orange dot -->
													</span>

													<span class="text-600 text-90 ml-1">
														<a href="#" class="btn-text-dark btn-h-text-primary">
															Jane
														</a>
													</span>

													<span class="text-80 text-grey-m2 ml-1">
														38 sec
													</span>
												</div>

												<div class="py-2 px-3 radius-1 bgc-green-d3 border-1 brc-black-tp11 text-white pos-rel">
													<!-- add this dark layer for a darker background -->
													<!-- <div class="position-tl w-100 h-100 bgc-black-tp10 radius-1"></div> -->
													<div class="text-90 pos-rel">
														Fusce dapibus, tellus ac cursus commodo... <span class='fa-stack w-auto'> <i
																class='fas fa-circle text-dark fa-stack-1x text-100'></i> <i
																class='fas fa-grin-alt text-warning-m3 text-125 fa-stack-1x pos-rel'></i> </span>
													</div>
												</div>
											</div>
										</div>



										<!-- if conversation item is on left -->
										<div class="d-flex flex-row-reverse px-3 text-right mb-1">
											<div>

												<div class="py-2 px-3 radius-1 bgc-green-d3 border-1 brc-black-tp11 text-white pos-rel">
													<!-- add this dark layer for a darker background -->
													<!-- <div class="position-tl w-100 h-100 bgc-black-tp10 radius-1"></div> -->
													<div class="text-90 pos-rel">
														Donec id elit non mi porta gravida at eget metus
													</div>
												</div>
											</div>
										</div>

									</div>
								</div>


								<div class="card-footer p-0 bgc-white border-t-1 brc-default-l1 radius-b-1">
									<div class="input-group flex-wrap p-3">
										<input type="text" placeholder="Type your message ..."
											class="shadow-none form-control form-control-lg brc-on-focus brc-primary-m2 border-0 border-b-1 pl-1 text-100" />

										<div class="input-group-append">
											<!-- the emoji icon and popup -->
											<div class="btn-group dropup">
												<button type="button" class="btn btn-sm btn-white bg-transparent border-0 py-2 dropdown-toggle"
													data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
													<span class="fa-stack w-auto">
														<i class="fas fa-circle text-dark fa-stack-1x text-140"></i>
														<i class="fas fa-smile text-warning-m3 text-160 fa-stack-1x pos-rel"></i>
													</span>
												</button>

												<div class="dropdown-menu dropdown-caret dropdown-menu-right p-1">
													<div class="d-flex ">
														<a class="dropdown-item px-25" href="#">
															<span class="fa-stack w-auto">
																<i class="fas fa-circle text-dark fa-stack-1x"></i>
																<i class="fas fa-smile text-warning-m3 text-125 fa-stack-1x pos-rel"></i>
															</span>
														</a>

														<a class="dropdown-item px-25" href="#">
															<span class="fa-stack w-auto">
																<i class="fas fa-circle text-dark fa-stack-1x"></i>
																<i class="fas fa-smile-beam text-warning-m3 text-125 fa-stack-1x pos-rel"></i>
															</span>
														</a>

														<a class="dropdown-item px-25" href="#">
															<span class="fa-stack w-auto">
																<i class="fas fa-circle text-dark fa-stack-1x"></i>
																<i class="fas fa-smile-wink text-warning-m3 text-125 fa-stack-1x pos-rel"></i>
															</span>
														</a>
													</div>
												</div>
											</div>
										</div>

										<div class="input-group-append d-block">
											<button class="btn btn-blue radius-round py-2 text-90 text-600" type="button">
												Send
												<i class="ml-1 ml-md-2 fa fa-paper-plane text-90"></i>
											</button>
										</div>
									</div><!-- /.input-group -->
								</div><!-- /.card-footer -->

							</div>
						</div>
					</div>
				</div>





			</div>


		</div>

	</div> <!-- /.body-container-->

</body>
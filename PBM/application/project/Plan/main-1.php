<?php include("../../../config/connect_db.php");
$page = "main";

?>
<!doctype html>
<html lang="en">

<body>
  <div class="body-container">


    <div class="main-container">

      <div role="main" class="main-content">
        <div class="page-content container container-plus px-md-4 px-xl-5">

          <div class="row" id="infobox-row">
            <div class="col-12 col-sm-6 col-lg-3 my-1 my-lg-0 px-sm-1 px-lg-0">
              <div class="d-flex align-items-center bgc-warning-d3 py-3" role="button">

                <div class="pos-rel p-3 bgc-white-tp8 radius-round ml-3 mr-3 shadow-md">
                  <i class="pos-abs mt-n2px ml-n3px fa fa-dollar-sign text-dark-tp5 opacity-5 w-5 h-5 text-center pt-1 text-170"></i>
                  <i class="pos-rel fa fa-dollar-sign text-white w-5 h-5 text-center pt-1 text-170"></i>
                </div>

                <div class="text-white">
                  <div>
                    <span class="text-160 text-600">$125,290</span>
                    <i class="fa fa-arrow-up ml-2"></i>
                  </div>
                  <div class="text-uppercase text-85 text-600 letter-spacing">
                    Revenue
                  </div>
                </div>

              </div>
            </div>


            <div class="col-12 col-sm-6 col-lg-3 my-1 my-lg-0 px-sm-1 px-lg-0">
              <div class="d-flex align-items-center bgc-pink-d3 py-3 border-l-3 brc-white-tp2" role="button">

                <div class="pos-rel p-3 bgc-white-tp8 radius-round ml-3 mr-3 shadow-md">
                  <i class="pos-abs mt-n1 ml-n1 fa fa-shopping-basket text-dark-tp5 opacity-5 w-5 h-5 text-center pt-1 text-170"></i>
                  <i class="pos-rel fa fa-shopping-basket text-white w-5 h-5 text-center pt-1 text-170"></i>
                </div>

                <div class="text-white">
                  <div>
                    <span class="text-160 text-600">$23,500</span>
                    <i class="fa fa-arrow-down ml-2"></i>
                  </div>
                  <div class="text-uppercase text-85 text-600 letter-spacing">
                    Spendings
                  </div>
                </div>

              </div>
            </div>


            <div class="col-12 col-sm-6 col-lg-3 my-1 my-lg-0 px-sm-1 px-lg-0">
              <div class="d-flex align-items-center bgc-primary-d2 py-3 border-l-3 brc-white-tp2" role="button">

                <div class="pos-rel p-3 bgc-white-tp8 radius-round ml-3 mr-3 shadow-md">
                  <i class="pos-abs mt-n1 fa fa-check text-dark-tp5 opacity-4 w-5 h-5 text-center pt-1 fa-2x"></i>
                  <i class="pos-rel fa fa-check text-white w-5 h-5 text-center pt-1 text-170"></i>
                </div>

                <div class="text-white">
                  <div>
                    <span class="text-160 text-600">20</span>
                    <i class="fa fa-plus text-white ml-1"></i>
                  </div>
                  <div class="text-uppercase text-85 text-600 letter-spacing">
                    Completed Tasks
                  </div>
                </div>

              </div>
            </div>


            <div class="col-12 col-sm-6 col-lg-3 my-1 my-lg-0 px-sm-1 px-lg-0">
              <div class="d-flex align-items-center bgc-default-d3 py-3 border-l-3 brc-white-tp2" role="button">

                <div class="pos-rel p-3 bgc-white-tp8 radius-round ml-3 mr-3 shadow-md">
                  <i class="pos-abs mt-n2px ml-n3px fa fa-retweet text-dark-tp5 opacity-5 w-5 h-5 text-center pt-1 text-170"></i>
                  <i class="pos-rel fa fa-retweet text-white w-5 h-5 text-center pt-1 text-170"></i>
                </div>

                <div class="text-white">
                  <div>
                    <span class="text-160 text-600">65%</span>
                    <i class="fa fa-arrow-right text-white ml-2"></i>
                  </div>
                  <div class="text-uppercase text-85 text-600 letter-spacing">
                    Conversion Rate
                  </div>
                </div>

              </div>
            </div>
          </div>





          <div class="row mt-45">
            <div class="col-lg-7 col-12 pl-lg-0 pr-lg-2">
              <!-- we could use '.card' here as well -->
              <div class="bcard radius-2 h-100 d-flex flex-column py-35 px-2 px-lg-4">
                <div class="d-flex mb-4">
                  <h4 class="text-secondary-d1 text-120 ml-1">
                    Quick Stats
                  </h4>

                  <div class="ml-auto d-flex align-items-center">
                    <div class="dropdown dd-backdrop dd-backdrop-none-md">
                      <a class="btn btn-light-blue btn-bgc-white btn-bold radius-2 px-3 btn-xs dropdown-toggle" href="#" role="button" data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
                        This Week
                      </a>

                      <div class="dropdown-menu shadow-sm dropdown-menu-right dropdown-caret dropdown-animated dd-slide-up dd-slide-none-md radius-1 brc-secondary-l1">
                        <div class="dropdown-inner">
                          <a class="dropdown-item active btn btn-a-lighter-primary btn-a-bold mx-1" href="#">This Week</a>
                          <a class="dropdown-item btn btn-a-lighter-primary text-secondary mx-1" href="#">Last Week</a>
                          <a class="dropdown-item btn btn-a-lighter-primary text-secondary mx-1" href="#">This Month</a>
                          <a class="dropdown-item btn btn-a-lighter-primary text-secondary mx-1" href="#">Last Month</a>
                        </div>
                      </div>
                    </div>

                    <div class="ml-1">
                      <a class="btn btn-outline-secondary btn-brc-tp btn-bold radius-round btn-xs dropdown-toggle" href="#">
                        <i class="fa fa-ellipsis-h"></i>
                      </a>
                    </div>
                  </div>
                </div>


                <div class="row mb-3 mx-1 px-1">
                  <div class="col-12 col-sm-4 px-0 d-flex justify-content-between d-sm-block">
                    <span class="text-grey-m1 ml-1">
                      revenue
                    </span>

                    <span class="text-success ml-2 mt-2 text-600 text-95 order-last">+4% <i class="fas fa-caret-up"></i></span>

                    <div class="d-sm-block flex-grow-1 text-right text-sm-left">
                      <span class="text-130 opacity-2">$</span>
                      <span class="text-secondary-d3 text-160">
                        46,220
                      </span>
                    </div>
                  </div>

                  <div class="col-12 col-sm-4 px-0 d-flex justify-content-between d-sm-block">
                    <span class="text-grey-m1 ml-1">
                      expense
                    </span>

                    <span class="text-orange ml-2 mt-2 text-600 text-95 order-last">+1% <i class="fas fa-caret-down"></i></span>

                    <div class="d-sm-block flex-grow-1 text-right text-sm-left">
                      <span class="text-130 opacity-2">$</span>
                      <span class="text-secondary-d3 text-160">
                        3,500
                      </span>
                    </div>
                  </div>

                  <div class="col-12 col-sm-4 px-0 d-flex justify-content-between d-sm-block">
                    <span class="text-grey-m1 ml-1">
                      customers
                    </span>

                    <span class="text-success ml-2 mt-2 text-600 text-95 order-last">+5% <i class="fas fa-caret-up"></i></span>

                    <div class="d-sm-block flex-grow-1 text-right text-sm-left">
                      <span class="text-secondary-d3 text-160">
                        315
                      </span>
                    </div>
                  </div>
                </div><!-- /.row -->

                <div>
                  <canvas id="quickstats-chart" height="120" class="mt-lg-4"></canvas>
                </div>
              </div>
            </div>

            <div class="col-lg-5 col-12 pr-lg-0 mt-3 mt-lg-0">
              <div class="bgc-white shadow-sm radius-2 pt-35 px-0 px-lg-4">
                <div class="d-flex mb-2">
                  <h4 class="text-secondary-d1 text-120 px-3 px-lg-0">
                    Task Overview
                  </h4>

                  <div class="ml-auto d-flex align-items-center">
                    <a class="btn btn-light-secondary btn-bgc-white btn-bold radius-2 px-3 btn-xs text-nowrap" href="#">
                      See All
                    </a>

                    <div class="ml-1 mr-2">
                      <a class="btn btn-outline-secondary btn-brc-tp btn-bold radius-round btn-xs dropdown-toggle" href="#">
                        <i class="fa fa-ellipsis-h"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="card border-0">
                  <div class="card-body px-0 px-sm-1 px-lg-0 border-t-1 brc-default-l3 table-responsive-sm">
                    <table class="table brc-grey-l4 mb-2">
                      <thead class="bgc-transparent">
                        <tr>
                          <th class="border-0 text-secondary-m2">
                            Title
                          </th>
                          <th class="border-0 text-secondary-m2">
                            Status
                          </th>
                          <th class="border-0 text-secondary-m2">
                            Progress
                          </th>
                        </tr>
                      </thead>

                      <tbody>

                        <tr class="bgc-h-default-l4" role="button">
                          <td class="text-dark-tp3 text-95">
                            Perform analysis
                          </td>
                          <td>
                            <span class="badge text-80 border-l-3 brc-black-tp8 bgc-green text-white">complete</span>



                          </td>
                          <td class="text-dark-m1 w-6">
                            <div class="align-self-center pos-rel text-green">
                              <canvas class="task-progress" height="50" width="80" data-percent="100"></canvas>
                              <span class="position-center text-80">
                                100%
                              </span>
                            </div>
                          </td>
                        </tr>
                        <tr class="bgc-h-default-l4" role="button">
                          <td class="text-dark-tp3 text-95">
                            Team meeting
                          </td>
                          <td>

                            <span class="badge text-80 border-l-3 brc-black-tp8 bgc-blue text-white">in progress</span>


                          </td>
                          <td class="text-dark-m1 w-6">
                            <div class="align-self-center pos-rel text-blue">
                              <canvas class="task-progress" height="50" width="80" data-percent="70"></canvas>
                              <span class="position-center text-80">
                                70%
                              </span>
                            </div>
                          </td>
                        </tr>
                        <tr class="bgc-h-default-l4" role="button">
                          <td class="text-dark-tp3 text-95">
                            Office redesign
                          </td>
                          <td>


                            <span class="badge text-80 border-l-3 brc-black-tp8 bgc-orange text-white">pending</span>

                          </td>
                          <td class="text-dark-m1 w-6">
                            <div class="align-self-center pos-rel text-orange-d2">
                              <canvas class="task-progress" height="50" width="80" data-percent="40"></canvas>
                              <span class="position-center text-80">
                                40%
                              </span>
                            </div>
                          </td>
                        </tr>
                        <tr class="bgc-h-default-l4" role="button">
                          <td class="text-dark-tp3 text-95">
                            Unnecessary task
                          </td>
                          <td>



                            <span class="badge text-80 border-l-3 brc-black-tp8 bgc-red text-white">cancelled</span>
                          </td>
                          <td class="text-dark-m1 w-6">
                            <div class="align-self-center pos-rel text-danger">
                              <canvas class="task-progress" height="50" width="80" data-percent="20"></canvas>
                              <span class="position-center text-80">
                                20%
                              </span>
                            </div>
                          </td>
                        </tr>
                        <tr class="bgc-h-default-l4" role="button">
                          <td class="text-dark-tp3 text-95">
                            Customer followup
                          </td>
                          <td>
                            <span class="badge text-80 border-l-3 brc-black-tp8 bgc-green text-white">complete</span>



                          </td>
                          <td class="text-dark-m1 w-6">
                            <div class="align-self-center pos-rel text-green">
                              <canvas class="task-progress" height="50" width="80" data-percent="95"></canvas>
                              <span class="position-center text-80">
                                95%
                              </span>
                            </div>
                          </td>
                        </tr>

                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>





          <div class="row mt-4 mt-lg-5">
            <div class="col-12 px-lg-0">
              <h4 class="text-dark-m3 pb-0 mb-3 text-140 pl-2">
                Calendar and Events
              </h4>

              <div class="bgc-white shadow-sm p-3 p-lg-4 radius-2">
                <div id='calendar' class="text-dark-l1"></div>
              </div>
            </div>
          </div>
        </div>



        <div class="footer-tools">
          <a href="#" class="btn-scroll-up btn btn-green px-25 py-2 text-95 radius-1 mb-2 mr-2">
            <i class="fa fa-angle-double-up w-2 h-2"></i>
          </a>
        </div>
      </div>

    </div>

  </div>

  <!-- include common vendor scripts used in demo pages -->
  <script src="../Template/ace/node_modules/jquery/dist/jquery.js"></script>
  <script src="../Template/ace/node_modules/popper.js/dist/umd/popper.js"></script>
  <script src="../Template/ace/node_modules/bootstrap/dist/js/bootstrap.js"></script>


  <!-- include vendor scripts used in "Horizontal Menu" page. see "/views//pages/partials/horizontal/@vendor-scripts.hbs" -->
  <script src="../Template/ace/node_modules/chart.js/dist/Chart.js"></script>

  <script src="../Template/ace/node_modules/fullcalendar/main.js"></script>


  <!-- include ace.js -->
  <script src="../Template/ace/dist/js/ace.js"></script>



  <!-- demo.js is only for Ace's demo and you shouldn't use it -->
  <script src="../Template/ace/app/browser/demo.js"></script>



  <!-- "Horizontal Menu" page script to enable its demo functionality -->
  <script src="../Template/ace/views/pages/horizontal/@page-script.js"></script>
</body>

</html>
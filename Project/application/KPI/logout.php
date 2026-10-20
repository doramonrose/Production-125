<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <!-- ===============================================-->
    <!--    Document Title-->
    <!-- ===============================================-->
    <title>app_backoffice/app/ECMU19/page.php</title>


    <!-- ===============================================-->
    <!--    Favicons-->
    <!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="../Template/falcon/public/assets/img/favicons/favicon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../Template/falcon/public/assets/img/favicons/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../Template/falcon/public/assets/img/favicons/favicon.png">
    <link rel="shortcut icon" type="image/x-icon" href="../Template/falcon/public/assets/img/favicons/favicon.png">
    <link rel="manifest" href="../Template/falcon/public/assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="../Template/falcon/public/assets/img/favicons/favicon.png">
    <meta name="theme-color" content="#ffffff">
    <script src="../Template/falcon/public/assets/js/config.js"></script>
    <script src="../Template/falcon/public/vendors/simplebar/simplebar.min.js"></script>


    <!-- ===============================================-->
    <!--    Stylesheets-->
    <!-- ===============================================-->
    <!-- Date -->
    <link href="../Template/falcon/public/vendors/flatpickr/flatpickr.min.css" rel="stylesheet" />
    <link href="../Template/falcon/public/vendors/dropzone/dropzone.css" rel="stylesheet" />
    <link href="../Template/falcon/public/vendors/prism/prism-okaidia.css" rel="stylesheet">


    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@100..700&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet"> -->
    <!-- <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet"> -->
    <!-- <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet"> -->
    <link href="../Template/falcon/public/vendors/simplebar/simplebar.min.css" rel="stylesheet">
    <link href="../Template/falcon/public/assets/css/theme-rtl.css" rel="stylesheet" id="style-rtl">
    <link href="../Template/falcon/public/assets/css/theme.css" rel="stylesheet" id="style-default">
    <link href="../Template/falcon/public/assets/css/user-rtl.css" rel="stylesheet" id="user-style-rtl">
    <link href="../Template/falcon/public/assets/css/user.css" rel="stylesheet" id="user-style-default">

    <script type="text/javascript" src="../Template/falcon/public/assets/js/sweetalert.min.js"></script>

    <script src="../Template/falcon/public/vendors/datatables.net-bs5/dataTables.bootstrap5.min.css"></script>
    <link href="../Template/falcon/public/vendors/select2/select2.min.css" rel="stylesheet">
    <link href="../Template/falcon/public/vendors/select2-bootstrap-5-theme/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="../Template/falcon/public/vendors/datatables.net-bs5/dataTables.bootstrap5.min.css" rel="stylesheet">

    <script>
        var isRTL = JSON.parse(localStorage.getItem('isRTL'));
        if (isRTL) {
            var linkDefault = document.getElementById('style-default');
            var userLinkDefault = document.getElementById('user-style-default');
            linkDefault.setAttribute('disabled', true);
            userLinkDefault.setAttribute('disabled', true);
            document.querySelector('html').setAttribute('dir', 'rtl');
        } else {
            var linkRTL = document.getElementById('style-rtl');
            var userLinkRTL = document.getElementById('user-style-rtl');
            linkRTL.setAttribute('disabled', true);
            userLinkRTL.setAttribute('disabled', true);
        }
    </script>
</head>

<body>

    <!-- ===============================================-->
    <!--    Main Content-->
    <!-- ===============================================-->
    <main class="main" id="top">
        <div class="container" data-layout="container">
            <script>
                var isFluid = JSON.parse(localStorage.getItem('isFluid'));
                if (isFluid) {
                    var container = document.querySelector('[data-layout]');
                    container.classList.remove('container');
                    container.classList.add('container-fluid');
                }
            </script>
            <div class="row flex-center min-vh-100 py-6">
                <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4"><a class="d-flex flex-center mb-4" href="../Template/falcon/public/index.html"><img class="me-2" src="../Template/falcon/public/assets/img/favicons/phar_65.png" alt="" width="58" /><span class="font-sans-serif text-primary fw-bolder fs-4 d-inline-block">PHARMACY MIS</span></a>
                    <div class="card">
                        <div class="card-body p-4 p-sm-5">
                            <div class="text-center"><img class="d-block mx-auto mb-4" src="../Template/falcon/public/assets/img/icons/spot-illustrations/45.png" alt="shield" width="100" />
                                <h4>See you again!</h4>
                                <p>Thanks for using. You are <br />now successfully signed out.</p><a class="btn btn-primary btn-sm mt-3" href="https://mis.pharmacy.cmu.ac.th/"><span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4 down-1"></span>Return to Login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- ===============================================-->
    <!--    End of Main Content-->
    <!-- ===============================================-->





    <!-- ===============================================-->
    <!--    JavaScripts-->
    <!-- ===============================================-->
    <script src="../Template/falcon/public/vendors/popper/popper.min.js"></script>
    <script src="../Template/falcon/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../Template/falcon/public/vendors/anchorjs/anchor.min.js"></script>
    <script src="../Template/falcon/public/vendors/is/is.min.js"></script>
    <script src="../Template/falcon/public/vendors/fontawesome/all.min.js"></script>
    <script src="../Template/falcon/public/vendors/lodash/lodash.min.js"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=window.scroll"></script>
    <script src="../Template/falcon/public/vendors/list.js/list.min.js"></script>
    <script src="../Template/falcon/public/assets/js/theme.js"></script>

</body>

</html>
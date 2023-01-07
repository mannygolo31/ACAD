<!DOCTYPE html>
<html lang="en">

<head><?php include "assets/includes/head.php";?></head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
      <a href="index.html" class="logo d-flex align-items-center">
        <img src="assets/img/logo.png" alt="">
        <span class="d-none d-lg-block">ADMIN</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div><!-- End Logo -->

    <div class="search-bar">
      <form class="search-form d-flex align-items-center" method="POST" action="#">
        <input type="text" name="query" placeholder="Search" title="Enter search keyword">
        <button type="submit" title="Search"><i class="bi bi-search"></i></button>
      </form>
    </div><!-- End Search Bar -->

    <?php include "assets/includes/navbar.php";?>
   <!-- End Icons Navigation -->

  </header><!-- End Header -->

  <!-- ======= Sidebar ======= -->
  <?php include "assets/includes/sidebar.php";?>
  <!-- End Sidebar-->

  <main id="main" class="main" >

	<!-- START MAIN CONTENT -->
  <body>
<div class="container" id="table">
    <div class="row">
    <table id="myTable" class="table table-striped" style="width:100%">
                    <!-- FETCHING DATA FROM DATABASE -->
                             <?php
                            //  INCLUDE DATABASE CONNECTION
                             include "assets/php/db_conn.php";
                            //  SQL QUERY
                                 $query = "SELECT * FROM `tabledb`";
                                 $query_run = mysqli_query($conn, $query);
                            ?>

                            <!-- TABLE HEADER -->
                        <thead class="table-dark">
                            <tr>
                                <th data-priority="1">ID</th>
                                <th>NAME</th>
                                <th>STDUDENT ID</th>
                                <th>SECTION</th>
                                <th>YEAR</th>
                            </tr>
                        </thead>


                        <!-- TABLE BODY / TABLE DATA -->
                        <tbody class="table-warning">
                            <?php

                            // FETCH DATA AS ROW

                            if(mysqli_num_rows($query_run) > 0)
                            {
                                foreach($query_run as $row)
                                {
                                    ?>
                                    <tr id="<?=$row['id'];?>">
                                    <!-- GET DATA FROM DATABASE AS ARRAY -->
                                        <td><?= $row['id'] ?></td>
                                        <td><?= $row['name'] ?></td>
                                        <td><?= $row['student_id'] ?></td>
                                        <td><?= $row['section'] ?></td>
                                        <td><?= $row['year'] ?></td>
                                        
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                            
                        </tbody>
                    </table>

    </div>
</div>

  <!-- Main content -->
  


<!-- DATATABLE SCRIPT CDN START -->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<!-- DATATABLE SCRIPT CDN END -->



<!-- DATA TABLE DEPLOYMENT USING SCRIPT START -->
    <script>
        $(document).ready(function () {
    $('#myTable').DataTable({
        dom: '<"top"i>rt<"bottom"flp><"clear">',
    });
});
    </script>
    <!-- DATA TABLE DEPLOYMENT USING SCRIPT END -->


    <!-- END MAIN CONTENT -->

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>NiceAdmin</span></strong>. All Rights Reserved
    </div>
    <div class="credits">
      <!-- All the links in the footer should remain intact. -->
      <!-- You can delete the links only if you purchased the pro version. -->
      <!-- Licensing information: https://bootstrapmade.com/license/ -->
      <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
      Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.min.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.min.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

<script>
      $(function () {
        //Initialize Select2 Elements
        $(".select2").select2();
       
        //Datemask dd/mm/yyyy
        $("#datemask").inputmask("dd/mm/yyyy", {"placeholder": "dd/mm/yyyy"});
        //Datemask2 mm/dd/yyyy
        $("#datemask2").inputmask("mm/dd/yyyy", {"placeholder": "mm/dd/yyyy"});
        //Money Euro
        $("[data-mask]").inputmask();

        //Date range picker
        $('#reservation').daterangepicker();
        //Date range picker with time picker
        $('#reservationtime').daterangepicker({timePicker: true, timePickerIncrement: 30, format: 'MM/DD/YYYY h:mm A'});
        //Date range as a button
        $('#daterange-btn').daterangepicker(
            {
              ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
              },
              startDate: moment().subtract(29, 'days'),
              endDate: moment()
            },
        function (start, end) {
          $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        }
        );

        //iCheck for checkbox and radio inputs
        $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
          checkboxClass: 'icheckbox_minimal-blue',
          radioClass: 'iradio_minimal-blue'
        });
        //Red color scheme for iCheck
        $('input[type="checkbox"].minimal-red, input[type="radio"].minimal-red').iCheck({
          checkboxClass: 'icheckbox_minimal-red',
          radioClass: 'iradio_minimal-red'
        });
        //Flat red color scheme for iCheck
        $('input[type="checkbox"].flat-red, input[type="radio"].flat-red').iCheck({
          checkboxClass: 'icheckbox_flat-green',
          radioClass: 'iradio_flat-green'
        });

        //Colorpicker
        $(".my-colorpicker1").colorpicker();
        //color picker with addon
        $(".my-colorpicker2").colorpicker();

        //Timepicker
        $(".timepicker").timepicker({
          showInputs: false
        });
      });
    </script>

<script type="text/javascript">
	
  $(document).on('submit', '#reg-form', function()
   {  
    $.post('submit.php', $(this).serialize(), function(data)
    {
     $(".result").html(data);  
     $("#form1")[0].reset();
    // $("#check").reset();

    });
    
    return false;
    
  
  })
</script>
<script>
$(".uncheck").click(function () {
    $('input:checkbox').removeAttr('checked');
});
</script>

</html>
<?php
include_once "_class/Koneksi.php";
{ // DEBUG
  // $_POST['getData'] = "barang";
  //die(json_encode($_POST));
}
{
   // paging
   $page = intval( isset($_POST['page']) ? $_POST['page'] : 1 );
   $nRows= intval( isset($_POST['rows']) ? $_POST['rows'] : 5 );
   $offset = ($page - 1) * $nRows;
   $LIMIT  = "LIMIT $nRows OFFSET $offset";
   // ceking order by
   $sort = isset($_POST['sort']) ? $_POST['sort'] : '';
   $order = isset($_POST['order']) ? $_POST['order'] : 'DESC';
   //$ORDER_BY = empty($sort) ? '' : "ORDER BY $sort $order";
   // cek value post getdata
   $getData = isset($_POST['getData']) ? $_POST['getData'] : 'm_satuan';
   // get seraching value
   $cari = isset($_POST['cari']) ? $_POST['cari'] : '';
   $multiQuery = [];
   $emptyMssg = "";
   $typeRows = "datagrid";
   $ROWS  = [
      'rows' => []
   ];
}
// switch getData
{
   switch($getData) {
      case "barang" :{
         $emptyMssg = "Data barang tidak tersedia";
         $WHERE    = empty($cari) ? "WHERE 1" : "WHERE `code`='$cari' OR `name` LIKE '$cari%'";
         $ORDER_BY =  "ORDER BY ".( empty($sort) ? 'code' : $sort )." $order";
         
         $code     = $_POST['code'] ?? '';
         if(!empty($code)){
            $code = explode(",", $code);
            $WHERE .= " AND code NOT IN ('". join("','", $code) ."')";
         }
         $multiQuery[] = "SELECT COUNT(0) as total FROM m_barang $WHERE";
         $multiQuery[] = "SELECT mb.*, ms.satuan_name 
                        FROM m_barang mb
                        LEFT JOIN m_satuan ms ON ms.satuan_code = mb.satuan_code
                        $WHERE $ORDER_BY $LIMIT";
        // die($multiQuery[0]);
      }break;
      case 'm_satuan':{
         $typeRows = "combobox";
         $multiQuery[] = "SELECT * FROM m_satuan";
      }break;
      default: break;
   }
}
// cek query is not empty
if(!count($multiQuery)) die(json_encode(['isMessage' => "You cannot access file"]));
{
   $koneksi_db = Koneksi::connect();
   if(mysqli_multi_query($koneksi_db, join(";", $multiQuery) )){
      do{
         if ($result = mysqli_store_result($koneksi_db)){
            while($rows = mysqli_fetch_assoc($result)){
               if(isset($rows['total'])) {
                  $ROWS = array_merge($ROWS, $rows);
               }else{
                  $ROWS['rows'][] = $rows;
               }
            }
         }
      }while( mysqli_more_results($koneksi_db) && mysqli_next_result($koneksi_db) );
   }

   // jika error query
   if(mysqli_error($koneksi_db)) $ROW['isMessage'] = "Error Query: ".mysqli_error($koneksi_db);
   
}

Koneksi::close();
echo json_encode( $typeRows == 'combobox' ? $ROWS['rows']  :  array_merge($ROWS, ['emptyMssg' => $emptyMssg]) );
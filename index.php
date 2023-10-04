<?php
/*
Plugin Name: Crop
Plugin URI: https://github.com/tguruslan/crop-wp
Description: Crop
Version: 1.1
Author: ruslan
Author URI: https://github.com/tguruslan
*/


add_action( 'admin_menu', function(){
  add_submenu_page( 'options-general.php', "Оптимізація зображень", "Оптимізація зображень", "administrator", "crop-page", "crop_page");
});

function crop_page(){
  echo "<style>
  #input-img{
    height:70vh;
    overflow-y:scroll;
    overflow-x:hidden;
  }
  #total a{
    float: right;
    cursor:pointer;
  }
  .id,.nonce{
    display:none;
  }
  #progres{
    width:calc( 100% - 10px );
    padding:5px;
    background:none;
  }
  #progres div{
    height:10px;
    background:#007cba;
    width:0;
  }

</style>";

echo "<h1>Оптимізація великих фото</h1><div id='input-img'><table class='wp-list-table widefat fixed striped posts'>";
echo '<label for="max_size"> Знайти фото більші за:<input type="number" class="max_size" name="max_size" min="100"></label>
<a class="find button button-primary">Знайти зображення</a>
<a class="clear button button-primary" style="display:none">Очистити результати</a>';
  echo '<thead>
  <tr id="row-0">
    <th width="20px">#</th>
    <th width="20px"><input type="checkbox"></th>
    <th>посилання</th>
    <th>ширина x висота</th>
    <th class="id">id</th>
    <th class="nonce">nonce</th>
    <th>нова ширина</th>
    <th>нова висота</th>
  </tr>
  </thead>
  <tbody></tbody>
  </table>
  </div>
  <div id="progres"><div>
  </div>
  </div>
  <br>
  <div id="total"> Оптимізовано <span class="how">0</span>/<span class="selected">0</span>
  <span class="of"> Всього неоптимізованих 0</span>
  <a class="optimize button button-primary">Оптимізувати зображення</a>
  </div>';

  function script_ajax(){
    ?>
    <script type="text/javascript" >
      jQuery(document).ready(function() {

        jQuery('#row-0 input').on("change", function() {
          if (jQuery(this).is(":checked")){
            jQuery("#input-img tr td input[type=checkbox]").prop('checked', true).trigger('change');
          }else{
            jQuery("#input-img tr td  input[type=checkbox]").prop('checked', false).trigger('change');
          }
        });


        jQuery(document).on("change", '#input-img tbody input', function() {
          var selected = 0;
          jQuery(document).find('tbody tr:has(input:checked)').each(function() {
            selected += 1;
          });
          jQuery(document).find('.selected').html(selected);
        });

        jQuery(document).find('a.find').click(function() {
          data={
            action: 'tgu_image_find',
            max_size:jQuery('.max_size').val()
          }
          jQuery.post( ajaxurl, data, function(out) {
            var count=0
            jQuery.each(jQuery.parseJSON(out),function(){
              count+=1;
              jQuery('table tbody').append('<tr>'+
              '<td>'+count+'</td>'+
              '<td><input type="checkbox"></td>'+
              '<td class="img_src">'+this[0]+'</td>'+
              '<td>'+this[1]+'</td>'+
              '<td class="id">'+this[2]+'</td>'+
              '<td class="nonce">'+this[3]+'</td>'+
              '<td class="width">'+this[4]+'</td>'+
              '<td class="height">'+this[5]+'</td>'+
              '</tr>');
            });
            if(count != 0){
              jQuery(document).find('.of').html(' Всього неоптимізованих '+count);
              jQuery(document).find('a.find').hide();
              jQuery(document).find('a.clear').show();
            }
          });
        });
        jQuery(document).find('a.clear').click(function() {
          jQuery('table tbody').html('');
          jQuery(document).find('a.clear').hide();
          jQuery(document).find('a.find').show();
        });

        jQuery('#total a.optimize').click(function() {
          var time = 3000;
          jQuery(document).find('tbody tr:has(input:checked)').each(function() {
            var id_img = jQuery(this).find('.id').text();
            var w_img = jQuery(this).find('.width').text();
            var h_img = jQuery(this).find('.height').text();
            var nonce = jQuery(this).find('.nonce').text();
            var image = jQuery(this).find('.img_src').text();
          setTimeout( function(){
            var data = {
              action: 'tgu_image',
              postid: id_img,
              fwidth: w_img,
              fheight: h_img,
            };
          jQuery.post( ajaxurl, data, function(out) {

              var how = jQuery('.how').html();
              var total_of = jQuery(document).find('.selected').html();
              total_of = parseInt(total_of);
              how = parseInt(how);
              how = how + 1;
              jQuery('.how').html(how);
              total_width = how / total_of * 100;
              jQuery('#progres div').css('width', total_width + '%');
              if(total_width == 100){
                alert('Всі обрані зображення оптимізовано!!!');
              }
          });
        }, time )
        time += 3000;
        });
      });
      });
    </script>
    <?php
  }
  script_ajax();
}

add_action('wp_ajax_tgu_image_find', function() {
  $query_images_args = array(
  'post_type' => 'attachment',
  'post_mime_type' =>'image',
  'post_status' =>'inherit',
  'posts_per_page' => -1,
  );

  $i = 0;
  $max_size = $_POST['max_size'];

  $query_images = new WP_Query( $query_images_args );
  $images = array();
  foreach ( $query_images->posts as $image) {
    $lol[] = $image->ID;
  }

  $responce=array();

  foreach ($lol as $lo) {
    $path = get_attached_file( $lo );
    $size = getimagesize($path);

    $image= wp_get_attachment_image_src( $lo, full );
    $image[0] = preg_replace('/[^\/]+\/\/[^\/]+/i', '', $image[0]);

    if ((($image[1] > $max_size) || ($image[2] > $max_size)) && ((intval($size[0]) > $max_size) || (intval($size[1]) > $max_size))) {
      if($image[1] > $image[2]){
        $w_0 = $max_size;
        $h_0 = intval($w_0 / ($image[1] / $image[2]));
      }else{
        $h_0 = $max_size;
        $w_0 = intval($h_0 / ($image[2] / $image[1]));
      }
      array_push($responce,array($image[0],$image[1].'x'.$image[2],$lo,wp_create_nonce("image_editor-$lo"),$w_0,$h_0));
    }
  }
    echo json_encode($responce);
    die();
});

add_action('wp_ajax_tgu_image', function() {
    $lo = $_POST['postid'];
    $fwidth = $_POST['fwidth'];
    $fheight = $_POST['fheight'];
    $path = get_attached_file( $lo );
    include plugin_dir_path( __FILE__ ).'/lib/WideImage.php';
    WideImage::loadFromFile($path)->resize($fwidth, $fheight)->saveToFile($path);
    echo "<div>".$path."</div>";
    die();
});

?>

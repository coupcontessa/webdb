<?php

namespace webdb\chart;

#####################################################################################################

function perpendicular_distance($x,$y,$L1x,$L1y,$L2x,$L2y)
{
  # https://www.loughrigg.org/rdp/viewsource.php
  if ($L1x==$L2x)
  {
    return abs($x-$L2x);
  }
  else
  {
    $m=(($L2y-$L1y)/($L2x-$L1x));
    $c=(0-$L1x)*$m+$L1y;
    return (abs($m*$x-$y+$c))/(sqrt($m*$m+1));
  }
}

#####################################################################################################

function chart_colors()
{
  $colors=array();
  $colors["teal"]=array(11,132,165);
  $colors["yellow"]=array(246,200,95);
  $colors["purple"]=array(111,78,124);
  $colors["light_green"]=array(157,216,102);
  $colors["red"]=array(202,71,47);
  $colors["light_red"]=array(254,200,216);
  $colors["orange"]=array(255,160,86);
  $colors["sky_blue"]=array(141,221,208);
  $colors["magenta"]=array(211,54,130);
  $colors["blue"]=array(38,139,210);
  $colors["grid"]=array(230,230,230);
  $colors["border"]=array(230,230,250);
  return $colors;
}

#####################################################################################################

function assign_discontinuous_plot_data($chart_data,$plot_data,$x_key,$y_key,$color_key,$marker="",$limits="update")
{
  # $segment_data[$i]["p1|2"][$x|y_key]
  $plot=array();
  $plot["color"]=$color_key;
  $plot["marker"]=$marker;
  $plot["segments"]=array();
  if ($limits=="assign")
  {
    $min_x=PHP_INT_MAX;
    $max_x=PHP_INT_MIN;
    $min_y=PHP_INT_MAX;
    $max_y=PHP_INT_MIN;
  }
  else
  {
    $min_x=$chart_data["x_min"];
    $max_x=$chart_data["x_max"];
    $min_y=$chart_data["y_min"];
    $max_y=$chart_data["y_max"];
  }
  $n=count($plot_data);
  for ($i=0;$i<$n;$i++)
  {
    $data=$plot_data[$i];
    $x1=$data["p1"][$x_key];
    $y1=$data["p1"][$y_key];
    $x2=$data["p2"][$x_key];
    $y2=$data["p2"][$y_key];
    $segment=array();
    $segment["p1"]=array("x"=>$x1,"y"=>$y1);
    $segment["p2"]=array("x"=>$x2,"y"=>$y2);
    $plot["segments"][]=$segment;
    if ($x1<$min_x)
    {
      $min_x=$x1;
    }
    if ($x1>$max_x)
    {
      $max_x=$x1;
    }
    if ($y1<$min_y)
    {
      $min_y=$y1;
    }
    if ($y1>$max_y)
    {
      $max_y=$y1;
    }
    if ($x2<$min_x)
    {
      $min_x=$x2;
    }
    if ($x2>$max_x)
    {
      $max_x=$x2;
    }
    if ($y2<$min_y)
    {
      $min_y=$y2;
    }
    if ($y2>$max_y)
    {
      $max_y=$y2;
    }
  }
  $chart_data["discontinuous_plots"][]=$plot;
  if (($limits=="update") or ($limits=="assign"))
  {
    if (($min_x<$max_x) and ($min_y<$max_y))
    {
      $chart_data["x_min"]=$min_x;
      $chart_data["x_max"]=$max_x;
      $chart_data["y_min"]=$min_y;
      $chart_data["y_max"]=$max_y;
    }
  }
  return $chart_data;
}

#####################################################################################################

function assign_plot_data($chart_data,$series_data,$x_key,$y_key,$color_key,$marker="",$assign_limits=true,$line_enabled=true,$name="")
{
  # $series_data[$i][$x|y_key] (continuous)
  $series=array();
  $series["name"]=$name;
  $series["color"]=$color_key;
  $series["type"]="plot";
  $series["marker"]=$marker;
  $series["line_enabled"]=$line_enabled;
  $series["x_values"]=array();
  $series["y_values"]=array();
  $min_x=PHP_INT_MAX;
  $max_x=0;
  $min_y=PHP_INT_MAX;
  $max_y=0;
  $n=count($series_data);
  for ($i=0;$i<$n;$i++)
  {
    $coord=$series_data[$i];
    $x=$coord[$x_key];
    $y=$coord[$y_key];
    $series["x_values"][]=$x;
    $series["y_values"][]=$y;
    if ($x<$min_x)
    {
      $min_x=$x;
    }
    if ($x>$max_x)
    {
      $max_x=$x;
    }
    if ($y<$min_y)
    {
      $min_y=$y;
    }
    if ($y>$max_y)
    {
      $max_y=$y;
    }
  }
  $chart_data["series"][]=$series;
  if ($assign_limits==true)
  {
    if (($min_x<=$max_x) and ($min_y<=$max_y))
    {
      $chart_data["x_min"]=$min_x;
      $chart_data["x_max"]=$max_x;
      $chart_data["y_min"]=$min_y;
      $chart_data["y_max"]=$max_y;
    }
  }
  return $chart_data;
}

#####################################################################################################

function initilize_chart()
{
  $data=array();
  $data["colors"]=\webdb\chart\chart_colors();
  $data["w"]=1800;
  $data["h"]=800;
  $data["left"]=60;
  $data["right"]=10;
  $data["bottom"]=60;
  $data["top"]=10;
  $data["series"]=array();
  $data["discontinuous_plots"]=array();
  $data["grid_x"]=1;
  $data["grid_y"]=1;
  $data["x_min"]=0;
  $data["x_max"]=10;
  $data["y_min"]=0;
  $data["y_max"]=10;
  $data["x_range_override"]=false;
  $data["y_range_override"]=false;
  $data["x_title"]="";
  $data["y_title"]="";
  $data["scale"]=1;
  $data["show_grid_x"]=true;
  $data["show_grid_y"]=true;
  $data["show_x_axis"]=true;
  $data["show_y_axis"]=true;
  $data["bg_color_r"]=253;
  $data["bg_color_g"]=253;
  $data["bg_color_b"]=253;
  $data["auto_grid_x_pix"]=30;
  $data["auto_grid_y_pix"]=30;
  return $data;
}

#####################################################################################################

function auto_range(&$data)
{
  $min_x=PHP_INT_MAX;
  $max_x=PHP_INT_MIN;
  $min_y=PHP_INT_MAX;
  $max_y=PHP_INT_MIN;
  for ($i=0;$i<count($data["discontinuous_plots"]);$i++)
  {
    $plot=$data["discontinuous_plots"][$i];
    $segments=$plot["segments"];
    $n=count($segments);
    for ($j=0;$j<$n;$j++)
    {
      $segment=$segments[$j];
      $x1=$segment["p1"]["x"];
      $y1=$segment["p1"]["y"];
      $x2=$segment["p2"]["x"];
      $y2=$segment["p2"]["y"];
      if ($x1<$min_x)
      {
        $min_x=$x1;
      }
      if ($x1>$max_x)
      {
        $max_x=$x1;
      }
      if ($x2<$min_x)
      {
        $min_x=$x2;
      }
      if ($x2>$max_x)
      {
        $max_x=$x2;
      }
      if ($y1<$min_y)
      {
        $min_y=$y1;
      }
      if ($y1>$max_y)
      {
        $max_y=$y1;
      }
      if ($y2<$min_y)
      {
        $min_y=$y2;
      }
      if ($y2>$max_y)
      {
        $max_y=$y2;
      }
    }
  }
  for ($i=0;$i<count($data["series"]);$i++)
  {
    $series=$data["series"][$i];
    $x_values=$series["x_values"];
    $y_values=$series["y_values"];
    $n=count($x_values);
    for ($j=0;$j<$n;$j++)
    {
      $x=$x_values[$j];
      $y=$y_values[$j];
      if ($x<$min_x)
      {
        $min_x=$x;
      }
      if ($x>$max_x)
      {
        $max_x=$x;
      }
      if ($y<$min_y)
      {
        $min_y=$y;
      }
      if ($y>$max_y)
      {
        $max_y=$y;
      }
    }
  }
  if ($min_x==$max_x)
  {
    if ($min_x>PHP_INT_MIN)
    {
      $min_x=$min_x-1;
    }
    if ($max_x<PHP_INT_MAX)
    {
      $max_x=$max_x+1;
    }
  }
  if ($min_y==$max_y)
  {
    if ($min_y>PHP_INT_MIN)
    {
      $min_y=$min_y-1;
    }
    if ($max_y<PHP_INT_MAX)
    {
      $max_y=$max_y+1;
    }
  }
  $data["x_min"]=$min_x;
  $data["x_max"]=$max_x;
  $data["y_min"]=$min_y;
  $data["y_max"]=$max_y;
  $dx=$max_x-$min_x;
  $dy=$max_y-$min_y;
  $data["grid_x"]=max(1,floor($data["auto_grid_x_pix"]/$data["w"]*$dx/10)*10);
  $data["grid_y"]=max(1,floor($data["auto_grid_y_pix"]/$data["h"]*$dy/10)*10);
}

#####################################################################################################

function get_time_captions($scale,&$data)
{
  $min_x=$data["x_min"];
  $max_x=$data["x_max"];
  $x_captions=array();
  switch ($scale)
  {
    case "day":
      # TODO
      break;
    case "month":
      $min_x=strtotime("-1 month",$min_x);
      $min_x=strtotime(date("M-Y",$min_x)."-01");
      $data["x_min"]=$min_x;
      $max_x=strtotime("+2 month",$max_x);
      $max_x=strtotime(date("M-Y",$max_x)."-01");
      $data["x_max"]=$max_x;
      $d1=new \DateTime("@".$min_x);
      $d2=new \DateTime("@".$max_x);
      $diff=$d1->diff($d2);
      $n=$diff->y*12+$diff->m;
      $x=$min_x;
      for ($i=0;$i<=$n;$i++)
      {
        $x_captions[]=date("M-Y",$x);
        $x=strtotime("+1 month",$x);
      }
      $data["grid_x"]=($max_x-$min_x)/$n;
      break;
    case "year":
      # TODO
      break;
  }
  $data["x_captions"]=$x_captions;
}

#####################################################################################################

function output_legend_line($data,$series)
{
  $chart_colors=$data["colors"];
  $color=$series["color"];
  $color=$chart_colors[$color];
  $w=60;
  $h=20;
  $buffer=imagecreatetruecolor($w,$h);
  $bg_color=imagecolorallocate($buffer,255,0,255); # magenta
  imagecolortransparent($buffer,$bg_color);
  imagefill($buffer,0,0,$bg_color);
  $line_color=imagecolorallocate($buffer,$color[0],$color[1],$color[2]);
  imageline($buffer,0,$h/2,$w-1,$h/2,$line_color);
  return \webdb\graphics\base64_image($buffer,"png");
}

#####################################################################################################

function output_chart($data,$filename=false,$no_output=false)
{
  global $settings;
  \webdb\chart\chart_draw_create($data);
  \webdb\chart\chart_draw_border($data);
  \webdb\chart\chart_draw_grid($data);
  for ($i=0;$i<count($data["discontinuous_plots"]);$i++)
  {
    \webdb\chart\chart_draw_discontinuous_plot($data,$data["discontinuous_plots"][$i]);
  }
  for ($i=0;$i<count($data["series"]);$i++)
  {
    $series=$data["series"][$i];
    switch ($series["type"])
    {
      case "plot":
        \webdb\chart\chart_draw_continuous_plot($data,$series);
        break;
      case "step":
        \webdb\chart\chart_draw_step_plot($data,$series);
        break;
    }
  }
  if (isset($data["today_mark"])==true)
  {
    \webdb\chart\chart_draw_today_mark($data);
  }
  if ($data["show_y_axis"]==true)
  {
    \webdb\chart\chart_draw_axis_y($data);
  }
  if ($data["show_x_axis"]==true)
  {
    \webdb\chart\chart_draw_axis_x($data);
  }
  if ($data["x_title"]!=="")
  {
    \webdb\chart\chart_draw_title_x($data);
  }
  if ($data["y_title"]!=="")
  {
    \webdb\chart\chart_draw_title_y($data);
  }
  if (($data["scale"]!=="") and ($data["scale"]<>1))
  {
    \webdb\graphics\scale_img($data["buffer"],$data["scale"],$data["w"],$data["h"]);
  }
  if ($no_output==true)
  {
    return $data;
  }
  if ($filename!==false)
  {
    $result=\webdb\chart\chart_draw_save_file($data,$filename);
  }
  else
  {
    $result=\webdb\chart\chart_draw_html_out($data);
  }
  \webdb\chart\chart_draw_destroy($data);
  return $result;
}

#####################################################################################################

function pixels_per_unit($pix,$min,$max)
{
  return (($pix-1)/($max-$min));
}

#####################################################################################################

function output_chart_pix_series($data,$key)
{
  $result=array();
  $s=$data["series"][$key];
  for ($i=0;$i<count($s["x_values"]);$i++)
  {
    $x=$s["x_values"][$i];
    $x=\webdb\chart\chart_to_pixel_x($x,$data);
    $y=$s["y_values"][$i];
    $y=\webdb\chart\chart_to_pixel_y($y,$data);
    $result[]=array($x,$y);
  }
  return $result;
}

#####################################################################################################

function get_caption($data,$series_key,$axis,$val)
{
  $captions=$data[$axis."_captions"];
  $min=$data[$axis."_min"];
  $max=$data[$axis."_max"];
  $delta=$max-$min;
  $grid=$data["grid_".$axis];
  $result=false;
  $min_error=$max;
  for ($i=0;$i<count($captions);$i++)
  {
    $test=$grid*$i+$min;
    $error=abs($test-$val);
    if ($error<$min_error)
    {
      $min_error=$error;
      $result=$i;
    }
  }
  if ($result!==false)
  {
    $result=$captions[$result];
  }
  return $result;
}

#####################################################################################################

function pixel_to_chart_x($pix,$data)
{
  $w=$data["w"];
  $left=$data["left"];
  $min=$data["x_min"];
  $max=$data["x_max"];
  $ppu=\webdb\chart\pixels_per_unit($pix,$min,$max);
  $chart_w=$w-$left-$right;
  return ($pix-$left)/$ppu+$min_x;
}

#####################################################################################################

function pixel_to_chart_y($pix,$data)
{
  $h=$data["h"];
  $bottom=$data["bottom"];
  $min=$data["y_min"];
  $max=$data["y_max"];
  $ppu=\webdb\chart\pixels_per_unit($pix,$min,$max);
  $chart_h=$h-$top-$bottom;
  return ($chart_h-$pix+$top-1)/$ppu+$min_y;
}

#####################################################################################################

function chart_to_pixel_x($val,$data)
{
  return \webdb\chart\real_to_pixel_x($data["w"],$data["left"],$data["right"],$data["x_min"],$data["x_max"],$val);
}

#####################################################################################################

function chart_to_pixel_y($val,$data)
{
  return \webdb\chart\real_to_pixel_y($data["h"],$data["top"],$data["bottom"],$data["y_min"],$data["y_max"],$val);
}

#####################################################################################################

function real_to_pixel_x($w,$left,$right,$min_x,$max_x,$rx)
{
  $chart_w=$w-$left-$right;
  return round(($rx-$min_x)*\webdb\chart\pixels_per_unit($chart_w,$min_x,$max_x))+$left;
}

#####################################################################################################

function real_to_pixel_y($h,$top,$bottom,$min_y,$max_y,$ry)
{
  $chart_h=$h-$top-$bottom;
  return ($chart_h-1-round(($ry-$min_y)*\webdb\chart\pixels_per_unit($chart_h,$min_y,$max_y)))+$top;
}

#####################################################################################################

function chart_draw_create(&$data)
{
  $data["buffer"]=imagecreatetruecolor($data["w"],$data["h"]);
  imageantialias($data["buffer"],true);
  $bg_color=imagecolorallocate($data["buffer"],$data["bg_color_r"],$data["bg_color_g"],$data["bg_color_b"]);
  imagefill($data["buffer"],0,0,$bg_color);
}

#####################################################################################################

function chart_draw_destroy(&$data)
{
  imagedestroy($data["buffer"]);
  unset($data["buffer"]);
}

#####################################################################################################

function chart_draw_border(&$data)
{
  $color=$data["colors"]["border"];
  $line_color=imagecolorallocate($data["buffer"],$color[0],$color[1],$color[2]);
  imagerectangle($data["buffer"],0,0,$data["w"]-1,$data["h"]-1,$line_color);
}

#####################################################################################################

function chart_draw_today_mark(&$data)
{
  $rx=time();
  if (($rx>$data["x_min"]) and ($rx<$data["x_max"]))
  {
    $color=$data["today_mark"];
    $color=$data["colors"][$color];
    $line_color=imagecolorallocate($data["buffer"],$color[0],$color[1],$color[2]);
    $px=\webdb\chart\chart_to_pixel_x($rx,$data);
    $py1=\webdb\chart\chart_to_pixel_y($data["y_max"],$data);
    $py2=\webdb\chart\chart_to_pixel_y($data["y_min"],$data);
    imageline($data["buffer"],$px,$py1,$px,$py2,$line_color);
  }
}

#####################################################################################################

function chart_draw_continuous_plot(&$data,$series)
{
  $color=$series["color"];
  $color=$data["colors"][$color];
  $line_color=imagecolorallocate($data["buffer"],$color[0],$color[1],$color[2]);
  $x_values=$series["x_values"];
  $y_values=$series["y_values"];
  $n=count($x_values)-1;
  for ($i=0;$i<$n;$i++)
  {
    $x1=\webdb\chart\chart_to_pixel_x($x_values[$i],$data);
    $y1=\webdb\chart\chart_to_pixel_y($y_values[$i],$data);
    if ($series["marker"]=="box")
    {
      imagerectangle($data["buffer"],$x1-2,$y1-2,$x1+2,$y1+2,$line_color);
    }
    $x2=\webdb\chart\chart_to_pixel_x($x_values[$i+1],$data);
    $y2=\webdb\chart\chart_to_pixel_y($y_values[$i+1],$data);
    if ($series["line_enabled"]==true)
    {
      imageline($data["buffer"],$x1,$y1,$x2,$y2,$line_color);
    }
  }
  if (($series["marker"]=="box") and ($n>0))
  {
    imagerectangle($data["buffer"],$x2-2,$y2-2,$x2+2,$y2+2,$line_color);
  }
}

#####################################################################################################

function chart_draw_step_plot(&$data,$series)
{
  $color=$series["color"];
  $color=$data["colors"][$color];
  $line_color=imagecolorallocate($data["buffer"],$color[0],$color[1],$color[2]);
  $x_values=$series["x_values"];
  $y_values=$series["y_values"];
  $x2=false;
  $y2=false;
  $n=count($x_values)-1;
  for ($i=0;$i<$n;$i++)
  {
    $min_x_exceeded=false;
    if ($x_values[$i+1]<$data["x_min"])
    {
      if ($x_values[$i+1]<>end($x_values))
      {
        continue;
      }
      else
      {
        $x_values[$i+1]=$data["x_min"];
        $min_x_exceeded=true;
      }
    }
    if ($x_values[$i]>$data["x_max"])
    {
      continue;
    }
    if ($x_values[$i]<$data["x_min"])
    {
      $x_values[$i]=$data["x_min"];
    }
    $max_x_exceeded=false;
    if ($x_values[$i+1]>$data["x_max"])
    {
      $x_values[$i+1]=$data["x_max"];
      $y_values[$i+1]=$y_values[$i];
      $max_x_exceeded=true;
    }
    $x1=\webdb\chart\chart_to_pixel_x($x_values[$i],$data);
    $y1=\webdb\chart\chart_to_pixel_y($y_values[$i],$data);
    $x2=\webdb\chart\chart_to_pixel_x($x_values[$i+1],$data);
    imageline($data["buffer"],$x1,$y1,$x2,$y1,$line_color);
    if ($max_x_exceeded==false)
    {
      $y2=\webdb\chart\chart_to_pixel_y($y_values[$i+1],$data);
      imageline($data["buffer"],$x2,$y1,$x2,$y2,$line_color);
      if ($min_x_exceeded==false)
      {
        imagerectangle($data["buffer"],$x2-2,$y2-2,$x2+2,$y2+2,$line_color);
      }
    }
  }
  $rx=time();
  if ((end($x_values)<$rx) and (isset($data["today_mark"])==true) and ($x2!==false) and ($y2!==false))
  {
    $x=\webdb\chart\chart_to_pixel_x($rx,$data);
    imageline($data["buffer"],$x2,$y2,$x,$y2,$line_color);
  }
}

#####################################################################################################

function chart_draw_discontinuous_plot(&$data,$plot)
{
  # $plot["p1|2"]["x|y_values"]
  $color=$plot["color"];
  $color=$data["colors"][$color];
  $line_color=imagecolorallocate($data["buffer"],$color[0],$color[1],$color[2]);
  $segments=$plot["segments"];
  $n=count($segments);
  for ($i=0;$i<$n;$i++)
  {
    $segment=$segments[$i];
    $x1=$segment["p1"]["x"];
    $y1=$segment["p1"]["y"];
    $x2=$segment["p2"]["x"];
    $y2=$segment["p2"]["y"];
    $x1=\webdb\chart\chart_to_pixel_x($x1,$data);
    $y1=\webdb\chart\chart_to_pixel_y($y1,$data);
    $x2=\webdb\chart\chart_to_pixel_x($x2,$data);
    $y2=\webdb\chart\chart_to_pixel_y($y2,$data);
    if ($plot["marker"]=="box")
    {
      imagerectangle($data["buffer"],$x1-2,$y1-2,$x1+2,$y1+2,$line_color);
    }
    imageline($data["buffer"],$x1,$y1,$x2,$y2,$line_color);
  }
  if (($plot["marker"]=="box") and ($n>0))
  {
    imagerectangle($data["buffer"],$x2-2,$y2-2,$x2+2,$y2+2,$line_color);
  }
}

#####################################################################################################

function chart_draw_grid(&$data)
{
  $color=$data["colors"]["grid"];
  $line_color=imagecolorallocate($data["buffer"],$color[0],$color[1],$color[2]);
  $dx=$data["x_max"]-$data["x_min"];
  $dy=$data["y_max"]-$data["y_min"];
  if ($data["show_grid_x"]==true)
  {
    $n=round($dx/$data["grid_x"]);
    for ($i=0;$i<=$n;$i++)
    {
      $rx=$data["grid_x"]*$i+$data["x_min"];
      $px=\webdb\chart\chart_to_pixel_x($rx,$data);
      imageline($data["buffer"],$px,$data["top"],$px,$data["h"]-$data["bottom"]-1,$line_color);
    }
  }
  if ($data["show_grid_y"]==true)
  {
    $n=round($dy/$data["grid_y"]);
    for ($i=0;$i<=$n;$i++)
    {
      $ry=$data["grid_y"]*$i+$data["y_min"];
      $py=\webdb\chart\chart_to_pixel_y($ry,$data);
      imageline($data["buffer"],$data["left"],$py,$data["w"]-$data["right"]-1,$py,$line_color);
    }
  }
}

#####################################################################################################

function chart_draw_axis_x(&$data)
{
  global $settings;
  $font_size=10;
  $tick_length=5;
  $label_space=4;
  $text_file=$settings["gd_ttf"];
  $line_color=imagecolorallocate($data["buffer"],50,50,50);
  $text_color=imagecolorallocate($data["buffer"],50,50,50);
  $y=\webdb\chart\chart_to_pixel_y($data["y_min"],$data);
  imageline($data["buffer"],$data["left"],$y,$data["w"]-$data["right"]-1,$y,$line_color);
  $grid_x_pixels=\webdb\chart\chart_to_pixel_x($data["grid_x"],$data);
  $dx=$data["x_max"]-$data["x_min"];
  $n=round($dx/$data["grid_x"]);
  for ($i=0;$i<=$n;$i++)
  {
    $rx=$data["grid_x"]*$i+$data["x_min"];
    $x=\webdb\chart\chart_to_pixel_x($rx,$data);
    $caption=$rx;
    if (isset($data["x_axis_format"])==true)
    {
      $caption=sprintf($data["x_axis_format"],$rx);
    }
    if (isset($data["x_captions"][$i])==true)
    {
      $caption=$data["x_captions"][$i];
    }
    $bbox=imagettfbbox($font_size,0,$text_file,$caption);
    $text_w=$bbox[2]-$bbox[0];
    $text_h=$bbox[1]-$bbox[7];
    if ($grid_x_pixels<($text_h*2))
    {
      if (($i%2)>0)
      {
        continue;
      }
    }
    imageline($data["buffer"],$x,$y,$x,$y+$tick_length,$line_color);
    imageline($data["buffer"],$x,$y+$tick_length,$x-$tick_length,$y+2*$tick_length,$line_color);
    $text_x=$x-round($text_w/sqrt(2))-$tick_length;
    $text_y=$y+round($text_w/sqrt(2))+2*$tick_length+$label_space+2;
    imagettftext($data["buffer"],$font_size,45,$text_x,$text_y,$text_color,$text_file,$caption);
  }
}

#####################################################################################################

function chart_draw_axis_y(&$data)
{
  global $settings;
  $font_size=10;
  $tick_length=5;
  $label_space=4;
  $text_file=$settings["gd_ttf"];
  $line_color=imagecolorallocate($data["buffer"],50,50,50);
  $text_color=imagecolorallocate($data["buffer"],50,50,50);
  $x=\webdb\chart\chart_to_pixel_x($data["x_min"],$data);
  imageline($data["buffer"],$x,$data["top"],$x,$data["h"]-$data["bottom"]-1,$line_color);
  $dy=$data["y_max"]-$data["y_min"];
  $n=round($dy/$data["grid_y"]);
  for ($i=0;$i<=$n;$i++)
  {
    $ry=$data["grid_y"]*$i+$data["y_min"];
    $y=\webdb\chart\chart_to_pixel_y($ry,$data);
    $caption=$ry;
    if (isset($data["y_axis_format"])==true)
    {
      $caption=sprintf($data["y_axis_format"],$ry);
    }
    if (isset($data["y_captions"][$i])==true)
    {
      $caption=$data["y_captions"][$i];
    }
    imageline($data["buffer"],$x,$y,$x-$tick_length,$y,$line_color);
    $bbox=imagettfbbox($font_size,0,$text_file,$caption);
    $text_w=$bbox[2]-$bbox[0];
    $text_h=$bbox[1]-$bbox[7];
    $text_x=$x-$text_w-$tick_length-$label_space;
    $text_y=$y+round($text_h/2);
    imagettftext($data["buffer"],$font_size,0,$text_x,$text_y,$text_color,$text_file,$caption);
  }
}

#####################################################################################################

function chart_draw_title_x(&$data)
{
  global $settings;
  $title_font_size=12;
  $title_margin=5;
  $text_color=imagecolorallocate($data["buffer"],50,50,50);
  $text_file=$settings["gd_ttf"];
  $cx=($data["w"]-$data["left"]-$data["right"])/2+$data["left"];
  $bbox=imagettfbbox($title_font_size,0,$text_file,$data["x_title"]);
  $text_w=$bbox[2]-$bbox[0];
  $text_h=$bbox[1]-$bbox[7];
  $text_x=$cx-round($text_w/2);
  $text_y=$data["h"]-$title_margin;
  imagettftext($data["buffer"],$title_font_size,0,$text_x,$text_y,$text_color,$text_file,$data["x_title"]);
}

#####################################################################################################

function chart_draw_title_y(&$data)
{
  global $settings;
  $title_font_size=12;
  $title_margin=5;
  $text_color=imagecolorallocate($data["buffer"],50,50,50);
  $text_file=$settings["gd_ttf"];
  $cy=($data["h"]-$data["bottom"]-$data["top"])/2+$data["top"];
  $bbox=imagettfbbox($title_font_size,0,$text_file,$data["y_title"]);
  $text_w=$bbox[2]-$bbox[0];
  $text_h=$bbox[1]-$bbox[7];
  $text_x=$title_margin+$text_h;
  $text_y=$cy+round($text_w/2);
  imagettftext($data["buffer"],$title_font_size,90,$text_x,$text_y,$text_color,$text_file,$data["y_title"]);
}

#####################################################################################################

function chart_draw_save_file(&$data,$filename)
{
  return imagepng($data["buffer"],$filename);
}

#####################################################################################################

function chart_draw_html_out(&$data)
{
  return \webdb\graphics\base64_image($data["buffer"],"png");
}

#####################################################################################################

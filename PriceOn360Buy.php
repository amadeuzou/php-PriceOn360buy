<?php
/****************************************************************************
*					
*			
* Copyright (c) 2012 by Amadeu zou, all rights reserved.
*
* Version:          0.0                                                     *
* Author:           Amadeu zou                                              *
* Contact:          amadeuzou AT gmail.com                                  *
* URL:              blog.youtueye.com                                 	    *
* Create Date:      2012-02-09         
*
* Description: Price Recognition on www.360buy.com price image
*              how-it-works:http://blog.youtueye.com/work/360buy-price.html
* Example:

require_once("PriceOn360Buy.php");
$price360 = new PriceOn360Buy('360buy_33.png');
echo sprintf("%16.2f", $price360->Price()).'<br>';

*****************************************************************************/

class PriceOn360Buy
{
	/* user definable vars */

	var $m_ImagePath;
	var $im_w;
	var $im_h;
	var $im;
    var $price;

	var $ERROR=array(
        'Load'=>'Cannot load the image!',
	    'Type'=>'Not a price image on 360buy!'
    );

	/*
	The construct function
	*/
    public function __construct($ImagePath)
	{
		$this->m_ImagePath = $ImagePath;
		$m_size = getimagesize($ImagePath);
		if(!$m_size)
		{
			exit($this->ERROR['Load']);
		}
		if(3 != $m_size[2])
		{
			exit($this->ERROR['Type']);
		}
		if($m_size[0]>200 || $m_size[0]<50 || $m_size[1]>30 || $m_size[1]<15)
		{
			exit($this->ERROR['Type']);
		}

		$this->im_w = $m_size[0];
		$this->im_h = $m_size[1];
		$this->im = imagecreatefrompng($ImagePath);
	}

    /*
	Price character recognition
	@param:
	@return: the float value of price
	*/
	public function Price()
	{
		// simple image segmentation by threshold
		$im_bw_data = $this->im2bw($this->im, $this->im_h, $this->im_w, 125);
		
		// projection on vertical 
		$m_proj = $this->projection($im_bw_data, $this->im_w, $this->im_h, 2);
	    $m_range = $this->range_vertical($m_proj);
	    $bw_vw = $this->im_w;
	    $bw_vh = $m_range[1]-$m_range[0]+1;

        // projection on horizontal
        $im_bw_small = $this->matrix_copy($im_bw_data, 0, $m_range[0], $bw_vw, $bw_vh);
        $m_proj = $this->projection($im_bw_small, $bw_vw, $bw_vh, 1);
	    $char_pos = $this->character_pos($m_proj);

		// match char by char mask
        $char_len = count($char_pos);
		$price_360 = "";
        for($i = 0; $i<$char_len; $i++){
            $im_char_h = $bw_vh;
            $im_char_w = $char_pos[$i][1] - $char_pos[$i][0] + 1;
	        $im_char_bw = $this->matrix_copy($im_bw_small, $char_pos[$i][0], 0, $im_char_w, $im_char_h);
	        $char_360 = $this->char_match($im_char_bw, $im_char_w, $im_char_h, $char_len);
	        if ('￥' != $char_360[1])
				$price_360 = $price_360.$char_360[1];
	    }
        
        $this->price = $price_360 + 0;
		return $this->price;
    }
    /*
	Destruct function
	*/
	public function __destruct()
	{
		imagedestroy($this->im);
	}

	/*******auxiliary functions*******/

	// char match
	private function char_match($im_char_bw, $im_char_w, $im_char_h, $char_len)
	{
		$thresh_max = array(3.3466,1.7321,3.2042,4.8305,1.4944,2.4037,3.8753,2.3094,2.2136,1.9120,2.6687);
		$thresh_min = array(2.6458,1.1547,3.6154,3.8668,1.7525,2.7999,3.4075,2.4495,2.5600,2.0310,3.1820);
		$char_list = array('￥','.','0','1','2','3','4','5','6','7','8','9');
		
		$m_proj = $this->projection($im_char_bw, $im_char_w, $im_char_h, 1);
		$m_ratio = $this->std($m_proj);

		if ($char_len<10)
		   $m_index = $this->find_element($thresh_max, $m_ratio);
		else
           $m_index = $this->find_element($thresh_min, $m_ratio);
        
		if(8==$m_index)
		{
		    $max_em = $this->max_element($m_proj);
			if($max_em[0] < count($m_proj)/2)
				$m_index = 8;
			else
				$m_index = 11;
		}

        $char_em[0] = $m_index - 2;
        $char_em[1] = $char_list[$m_index];
		return $char_em;
	}
    
	// max element loction
	private function max_element($m_arr)
	{
		$m_len = count($m_arr);
		$max_em = array();
		$m_value = 0;
		$m_index = 0;

		for($i = 0; $i<$m_len; $i++)
		{
			if($m_arr[$i] > $m_value)
			{
				$m_value = $m_arr[$i];
				$m_index = $i;
			}
		}

        $max_em[0] = $m_index;
		$max_em[1] = $m_value;

		return $max_em;

	}
	// find element
	private function find_element($m_arr, $m_em)
	{
		$m_len = count($m_arr);
		$m_index = 0;
		$m_dist = 99.9;

		for($i = 0; $i<$m_len; $i++)
		{
			if(abs($m_arr[$i] - $m_em) < $m_dist)
			{
				$m_dist = abs($m_arr[$i] - $m_em);
				$m_index = $i;
			}
		}

		return $m_index;
	}

    // char area ratio
	private function char_ratio($im_char_bw, $im_char_w, $im_char_h)
	{
		$m_proj = $this->projection($im_char_bw, $im_char_w, $im_char_h, 1);
		$m_ratio = $this->std($m_proj);

		return $m_ratio;
	}

    // matrix copy
	private function matrix_copy($src, $x, $y, $w, $h)
	{
		$dst = array();
		for($i=0; $i < $w; $i++)
		{
			for($j=0; $j < $h; $j++)
			{
				$dst[$i][$j] = $src[$i + $x][$j + $y];
			}
		}

		return $dst;
	}

	// vector processing
	private function mean($m_vec)
	{
		$m_count = count($m_vec);
		$m_mean = 0;
        for($i = 0; $i<$m_count; $i++)
            $m_mean = $m_mean + $m_vec[$i];
		$m_mean = $m_mean / $m_count;
		return $m_mean;
	}
    private function std($m_vec)
	{
		$m_count = count($m_vec);
		$m_mean = $this->mean($m_vec);
		$m_std = 0;

        for($i = 0; $i<$m_count; $i++)
            $m_std = $m_std + ($m_vec[$i] - $m_mean)*($m_vec[$i] - $m_mean);
		$m_std = sqrt($m_std / ($m_count - 1));

		return $m_std;
	}

	//projection
	private function projection($im_bw_data, $im_w, $im_h, $m_model)
	{
	$m_proj = array(); 
	if(1 == $m_model )
	{
		for($i=0; $i < $im_w; $i++)
		{
			$m_sum = 0;
			for($j=0; $j < $im_h; $j++)
			{
				$m_sum = $m_sum + $im_bw_data[$i][$j];
			}
			$m_proj[$i] = $m_sum;
		}
	}
	else
	{
        for($j=0; $j < $im_h; $j++)
		{
			$m_sum = 0;
			for($i=0; $i < $im_w; $i++)
			{
				$m_sum = $m_sum + $im_bw_data[$i][$j];
			}
			$m_proj[$j] = $m_sum;
		}
	}

	return $m_proj;

	}

	// vertical horizontal 
	private function range_vertical($m_proj)
	{
		$m_range = array(); 
		$m_count = count($m_proj);
        $m_mean = $this->mean($m_proj);

		for($i=0; $i<$m_count; $i++)
		{
			if($m_proj[$i] <= $m_mean)
				continue;
            $m_range[0] = $i - 1;
			break;
		}

		for($i=$m_count-1; $i>=0; $i--)
		{
			if($m_proj[$i] <= $m_mean)
				continue;
            $m_range[1] = $i + 1;
			break;
		}

		return $m_range;
	}

	//character split
	private function character_pos($m_proj)
	{
		$m_length = count($m_proj);
		$m_threshold = 1;
		$char_pos = array(); 
        
        $i = 0;
		$m_count = 0;
        $p_min = 0;
		$p_max = $m_length - 1;

		while($i < $m_length )
		{
			if($m_proj[$i] >= $m_threshold)
			{
				$p_min = $i;
				while($i < $m_length )
				{
					$i = $i + 1;
					if($m_proj[$i] < $m_threshold)
					{
						$p_max = $i;
						$char_pos[$m_count][0] = $p_min;
						$char_pos[$m_count][1] = $p_max;
						$m_count = $m_count + 1;
						break;
					}
				}
			}//if

			$i = $i + 1;
			if ($i > $m_length - 1)
				break;

		}//loop

		return $char_pos;
        
	}

	/*
    Color Image to Binary Image
	*/
	private function im2bw($im_res, $im_h, $im_w, $level)
	{
		$im_bw_data = array(); 
        for($j=0; $j < $im_h; $j++)  
        {  
            for($i=0; $i < $im_w; $i++)  
            {  
                $rgb = imagecolorat($im_res,$i,$j);  
                //$rgbarray = imagecolorsforindex($res, $rgb); 
				//$rgbarray['red'] $rgbarray['green'] $rgbarray['blue'] 
                //$r = ($rgb >> 16) & 0xFF;
                //$g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if($b < $level)  
                {  
                    $im_bw_data[$i][$j]=1; 
                }else{  
                    $im_bw_data[$i][$j]=0; 
                }  
                
            }  
        } 

		return $im_bw_data;
	}

} // end of class
?>

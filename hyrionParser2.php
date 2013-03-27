<?php
/**
	 * Hyrion Parser
     * Copyright (C) 2012 Maarten Oosting, Kevin van Steijn
     * http://hyrion.com
     *
     * This program is free software; you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation; either version 2 of the License, or
     * (at your option) any later version.
     * 
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License along
     * with this program; if not, write to the Free Software Foundation, Inc.,
     * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/
require_once('updatecheck.php');
class Hyrion_parser extends updatecheck 
{
	
	Public $content;
	
	Private $error = false;
	
	public function parse($filename,$data='')
	{
		if(isset($filename))
		{
			$content = $this->get_file($filename);
			if($content)
			{
				if(isset($data))
				{
					//Hier returnt hij de content naar de controller
					$content = $this->parce_ifs($content);
					return $this->start_parce($content,$data);
				}else{
					//only include
					return $content;
				}
			}else{
				return false;
			}
		}
	}
	
	private function get_file($filename)
	{
		$filename = $filename;
		if(file_exists($filename))
		{
			return file_get_contents($filename);
		}else{
			return false;
		}
	}
	
	private function start_parce($content,$data)
	{
		
		if($content == '' || empty($content))
		{
			//Als er geen content is dan return False
			return false;
		}
		
		foreach($data as $key => $val)
		{
			if(!is_array($val))
			{
				$content = $this->parse_one($key,$val,$content);		
			}
			else
			{
				//als er meerdere values zijn in de array
				$content = $this->parse_array($key,$val,$content);
			}
		}
		
		return $content;
	}
	
	private function parse_one($key, $val, $content)
	{
		$key = "{".$key."}";
		return str_replace($key, $val, $content);
	}
	
	private function parse_array($var,$data,$content)
	{
		if (false === ($match = $this->match($content, $var)))
		{
			return $content;
		}
		$data_all = '';
		if(!empty($data))
		{
			foreach($data as $value)
			{
				$cache = $match['1'];
				foreach($value as $key => $val)
				{
					if(is_array($val))
					{
						$cache = $this->parse_array($key,$val,$cache);
					}else{
						$cache = $this->parse_one($key,$val,$cache);
					}
				}
				$data_all .= $cache;
			}
		}
		return str_replace($match['0'], $data_all, $content);
	}
	
	private function match($content, $var)
	{
		if(!preg_match("|{".$var."}(.+?){/".$var."}|s", $content, $match))
		{
			return FALSE;
		}else{
			return $match;
		}
	}
	
	private function parce_ifs($content)
	{
		if (!preg_match_all("|".preg_quote ('<!-- IF')." (.+?) ".preg_quote ('-->')."(.+?)".preg_quote ('<!-- END IF -->')."|s", $content, $match))
		{
			//echo "false!";
		}else{
			foreach($match[1] as $key2=>$val2)
			{
				if(preg_match("|(.+?)\((.+?)\) \=\= ([A-Za-z0-9]{1,})(.+?)|s", $val2, $match2))
				{
					if(!preg_match("|[\W]+|s", $match2[1], $match3))
					{
						$functions = new Parser_functions();
						if(!preg_match("|".preg_quote ('<!-- ELSE -->')."|s", $match[2][$key2], $match3))
						{
							if(isset($match2[2]))
							{
								if($functions->$match2[1]($match2[2]) == $match2[3])
								{
									$start_tag = "<!-- IF ".$val2." -->";
									$content = preg_replace("|".preg_quote($start_tag)."(.+?)".preg_quote ('<!-- END IF -->')."|s", $match[2][$key2], $content,1);
								}else{
									$start_tag = "<!-- IF ".$val2." -->";
									$content = preg_replace("|".preg_quote($start_tag)."(.+?)".preg_quote ('<!-- END IF -->')."|s", "", $content,1);
								}
							}else{
								if($functions->$match2[1]() == $match2[3])
								{
									$start_tag = "<!-- IF ".$val2." -->";
									$content = preg_replace("|".preg_quote($start_tag)."(.+?)".preg_quote ('<!-- END IF -->')."|s", $match[2][$key2], $content,1);
								}else{
									$start_tag = "<!-- IF ".$val2." -->";
									$content = preg_replace("|".preg_quote($start_tag)."(.+?)".preg_quote ('<!-- END IF -->')."|s", "", $content,1);
								}
							}
						}else{
							if(isset($match2[2]))
							{
								$match[2][$key2] .= "<!-- END IF -->";
								preg_match("|(.+?)\<\!\-\- ELSE \-\-\>(.+?)\<\!\-\- END IF \-\-\>|s", $match[2][$key2], $match4);
								if($functions->$match2[1]($match2[2]) == $match2[3])
								{
									$start_tag = "<!-- IF ".$val2." -->";
									$content = preg_replace("|".preg_quote($start_tag)."(.+?)".preg_quote ('<!-- END IF -->')."|s", $match4[1], $content,1);
								}else{
									$start_tag = "<!-- IF ".$val2." -->";
									$content = preg_replace("|".preg_quote($start_tag)."(.+?)".preg_quote ('<!-- END IF -->')."|s", $match4[2], $content,1);
								}
							}else{
								$match[2][$key2] .= "<!-- END IF -->";
								preg_match("|(.+?)\<\!\-\- ELSE \-\-\>(.+?)\<\!\-\- END IF \-\-\>|s", $match[2][$key2], $match4);
								if($functions->$match2[1]() == $match2[3])
								{
									$start_tag = "<!-- IF ".$val2." -->";
									$content = preg_replace("|".preg_quote($start_tag)."(.+?)".preg_quote ('<!-- END IF -->')."|s", $match4[1], $content,1);
								}else{
									$start_tag = "<!-- IF ".$val2." -->";
									$content = preg_replace("|".preg_quote($start_tag)."(.+?)".preg_quote ('<!-- END IF -->')."|s", $match4[2], $content,1);
								}
							}
						}
					}else{
						//throw error!
					}
				}
			}
		}
		
		
		return $content;
	}
}
<?php

if(!function_exists("pr"))
{
	function pr($data = NULL)
	{
		echo "<pre>";print_r($data);echo "</pre>";
	}
}

if(!function_exists("prd"))
{
	function prd($data = NULL)
	{
		echo "<pre>";print_r($data);echo "</pre>";die;
	}
}
<?php
/**
 * Provides a basic set of math operations available remotely
 * for testing the web service platform. If this service is functioning
 * then your Siphon system was set up correctly.
 *
 * @package siphon
 * @author Colin Bate
 **/
class Math {
	
	/**
	 * Adds two, optionally three, numbers together.
	 *
	 * @return float
	 * @param float $a
	 * @param float $b
	 * @param float $c
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function add($a, $b, $c = 0) {
		return (float)$a + $b + $c;
	}
	
	/**
	 * Subtract b from a.
	 * Does this handle multiple lines?
	 *
	 * @return float
	 * @param float $a
	 * @param float $b
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function subtract($a, $b) {
		return (float)$a - $b;
	}
	
	/**
	 * Multiply two numbers together.
	 *
	 * @return float
	 * @param float $a
	 * @param float $b
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function multiply($a, $b) {
		return (float)$a * $b;
	}
	
	/**
	 * Divides a by b.
	 *
	 * @return float
	 * @param float $a
	 * @param float $b
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function divide($a, $b) {
		return (float)$a / $b;
	}
	
	/**
	 * Returns half a number.
	 *
	 * @return float
	 * @param float $number
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function half($number) {
		return ($number / 2.0);
	}
	
	/**
	 * Counts the number of characters in a string.
	 *
	 * @return int
	 * @param string $str
	 * @author Colin Bate <colin@rhuvium.com>
	 **/
	public function charCount($str) {
		return strlen($str);
	}
} // END public class Math
?>
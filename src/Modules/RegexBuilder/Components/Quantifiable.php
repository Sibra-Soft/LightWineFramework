<?php
namespace LightWine\Modules\RegexBuilder\Components;

class Quantifiable
{
	protected $greed = 'greedy';

	protected $min = 1;

	protected $max = 1;

	/**
	 * Match either once or not at all.
	 *
	 * @return $this
	 */
	public function Optional()
	{
		$this->min = 0;
		$this->max = 1;
		return $this;
	}

	/**
	 * Match between $min and $max times, inclusive.
	 *
	 * @param $min
	 * @param $max
	 * @return $this
	 */
	public function Interval($min, $max)
	{
		$this->min = $min;
		$this->max = $max;
		return $this;
	}

	/**
	 * Match at lewst $min times.
	 *
	 * @param $min
	 * @return $this
	 */
	public function AtLeast($min)
	{
		$this->min = $min;
		$this->max = null;
		return $this;
	}

	/**
	 * Match exactly $number times.
	 *
	 * @param $number
	 * @return $this
	 */
	public function Times($number)
	{
		$this->min = $number;
		$this->max = $number;
		return $this;
	}

	/**
	 * Match at most $max times.
	 *
	 * @param $max
	 * @return $this
	 */
	public function AtMost($max)
	{
		$this->min = 0;
		$this->max = $max;
		return $this;
	}

	/**
	 * Match one or more times.
	 *
	 * @return $this
	 */
	public function OneOrMore()
	{
		$this->min = 1;
		$this->max = null;
		return $this;
	}

	/**
	 * Don't match at all, or any number of times.
	 *
	 * @return $this
	 */
	public function ZeroOrMore()
	{
		$this->min = 0;
		$this->max = null;
		return $this;
	}

	/**
	 * Match as little characters as possible while still making a match.
	 *
	 * @return $this
	 */
	public function Lazy()
	{
		$this->greed = 'lazy';
		return $this;
	}

	/**
	 * Match as much characters as possible,
	 * even if this means that no match can be made.
	 *
	 * @return $this
	 */
	public function Possessive()
	{
		$this->greed = 'possessive';
		return $this;
	}

	/**
	 * @return string
	 */
	protected function GetModifierString()
	{
		if ($this->min === 0) {
			if ($this->max === 0) {
				$exp = "{0}";
			} elseif ($this->max == 1) {
				$exp = "?";
			} elseif ($this->max === null) {
				$exp = "*";
			} else {
				$exp = "{" . $this->min . "," . $this->max . "}";
			}
		} elseif ($this->min == 1) {
			if ($this->max == 1) {
				$exp = "";
			} elseif ($this->max === null) {
				$exp = "+";
			} else {
				$exp = "{" . $this->min . "," . $this->max . "}";
			}

		} elseif ($this->min == $this->max) {
			$exp = "{" . $this->min . "}";
		} else {
			$exp = "{" . $this->min . "," . $this->max . "}";
		}

		if ($this->greed == 'possessive') {
			if ($this->max !== $this->min) {
				$exp .= '+';
			}
		} elseif ($this->greed == 'lazy') {
			if ($this->max !== $this->min) {
				$exp .= '?';
			}
		}

		return $exp;
	}
}
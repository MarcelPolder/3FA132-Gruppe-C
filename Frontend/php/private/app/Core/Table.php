<?php
namespace Webapp\Core;

class Table {
	private array $data;

	private array|bool $footer = false;
	private string $sortColumn = "";
	private bool $sortDesc = false;
	private array $sortDisable = [];
	private string $title = "";
	private bool $layoutFixed = true;
	private bool $headerCapitalize = true;
	private bool $headerAside = false;
	private array $aliases = [];
	private array $colspan = [];
	private array $rowspan = [];
	private array $align = [];
	private array $valign = [];
	private array $width = [];
	private $formatings = [];

	/**
	 * Creates a new Table instance.\
	 *
	 * @param array $data
	 * @return Table
	 * 
	 * \
	 * Example:
	 * ```php
	 * $data = [
	 *     [
	 *         'Column Heading' => 'Value',
	 *         'Column Heading 2' => 'Value 2',
	 *         'Column Heading 3' => 'Value 3',
	 *         'Column Heading 4' => 'Value 4',
	 *         'Column Heading 5' => 'Value 5',
	 *     ],
	 *     [
	 *         'Column Heading' => 'Value',
	 *         'Column Heading 2' => 'Value 2',
	 *         'Column Heading 3' => 'Value 3',
	 *         'Column Heading 4' => 'Value 4',
	 *         'Column Heading 5' => 'Value 5',
	 *     ],
	 *     [
	 *         'Column Heading' => 'Value',
	 *         'Column Heading 2' => 'Value 2',
	 *         'Column Heading 3' => 'Value 3',
	 *         'Column Heading 4' => 'Value 4',
	 *         'Column Heading 5' => 'Value 5',
	 *     ],
	 * ];
	 * ```
	 */
	public function __construct(array $data) {
		if(count($data) == count($data, COUNT_RECURSIVE)) $data = [$data];
		$this->data = $data;
	}

	/**
	 * Sorts the values by the given columns.
	 * 
	 * @param string $column
	 * @param bool $desc defines if the order should be descending (```true```) or ascending (```false```, default).
	 * @return Table
	 */
	public function sort(string $column, bool $desc = false): self {
		if(!empty($column)) {
			$this->sortColumn = $column;
			$this->sortDesc = $desc;
			$this->_sort($column, $desc);
		}
		return $this;
	}

	/**
	 * Disables the sorting for the given columns.
	 * 
	 * @param array $columns
	 * @return Table
	 */
	public function sortDisable(array $columns): self {
		if(!empty($columns)) $this->sortDisable = $columns;
		return $this;
	}

	/**
	 * Sets the title (```<caption>```) of the table.
	 *
	 * @param string $title
	 * @return Table
	 */
	public function title(string $title): self {
		$this->title = $title;
		return $this;
	}

	/**
	 * Sets the footer of the table.
	 *
	 * @param array|bool $footer if ```true``` footer columns equals header columns.
	 * @return Table
	 * 
	 * \
	 * Example:
	 * ```php
	 * $footer = [
	 *     [
	 *         'Column Footer' => 'Value',
	 *         'Column Footer 2' => 'Value 2',
	 *         'Column Footer 3' => 'Value 3',
	 *         'Column Footer 4' => 'Value 4',
	 *         'Column Footer 5' => 'Value 5',
	 *     ],
	 *     [
	 *         'Column Footer' => 'Value',
	 *         'Column Footer 2' => 'Value 2',
	 *         'Column Footer 3' => 'Value 3',
	 *         'Column Footer 4' => 'Value 4',
	 *         'Column Footer 5' => 'Value 5',
	 *     ],
	 *     [
	 *         'Column Footer' => 'Value',
	 *         'Column Footer 2' => 'Value 2',
	 *         'Column Footer 3' => 'Value 3',
	 *         'Column Footer 4' => 'Value 4',
	 *         'Column Footer 5' => 'Value 5',
	 *     ],
	 * ];
	 * ```
	 */
	public function footer(array|bool $footer): self {
		$this->footer = $footer;
		return $this;
	}

	/**
	 * Sets colspan attribute for specific columns.
	 *
	 * @param array $columns
	 * @return Table
	 * 
	 * \
	 * Example:
	 * ```php
	 * $columns = [
	 *     'Column Heading' => 2,
	 *     'Column Heading 2' => 2,
	 * ];
	 * ```
	 */
	public function colspan(array $columns): self {
		$this->colspan = $columns;
		return $this;
	}

	/**
	 * Sets rowspan attribute for specific columns.
	 *
	 * @param array $columns
	 * @return Table
	 * 
	 * \
	 * Example:
	 * ```php
	 * $columns = [
	 *     'Column Heading' => 2,
	 *     'Column Heading 2' => 2,
	 * ];
	 * ```
	 */
	public function rowspan(array $columns): self {
		$this->rowspan = $columns;
		return $this;
	}

	/**
	 * Sets the header columns on the left side.
	 *
	 * @return self
	 */
	public function headerAside(): self {
		$this->headerAside = true;
		$this->layoutAuto();
		return $this;
	}

	/**
	 * Sets the table layout to auto.
	 *
	 * @return Table
	 */
	public function layoutAuto(): self {
		$this->layoutFixed = false;
		return $this;
	}

	/**
	 * Whether column headings should be automatically be capitalized.
	 *
	 * @param bool $capitalized (```true```, default)
	 * @return Table
	 */
	public function capitalizeHeadings(bool $capitalized = true): self {
		$this->headerCapitalize = $capitalized;
		return $this;
	}

	/**
	 * Sets aliases for column headings or footer columns.
	 *
	 * @param array $aliases
	 * @return Table
	 * 
	 * \
	 * Example:
	 * ```php
	 * $aliases = [
	 *     'Column Heading' => 'My Column Heading',
	 *     'Column Heading 2' => 'My Column Heading 2',
	 * ];
	 * ```
	 */
	public function alias(array $aliases): self {
		$this->aliases = $aliases;
		return $this;
	}

	/**
	 * Sets alignment for columns.
	 *
	 * @param array $align
	 * @return Table
	 * 
	 * \
	 * Example:
	 * ```php
	 * $align = [
	 *     'Column Heading' => 'left',
	 *     'Column Heading' => 'right',
	 *     'Column Heading' => 'center',
	 *     'Column Heading' => 'start',
	 *     'Column Heading' => 'end',
	 *     'Column Heading' => 'justify',
	 * ];
	 * ```
	 */
	public function align(array $align): self {
		$this->align = $align;
		return $this;
	}

	/**
	 * Sets vertical alignment for columns.
	 *
	 * @param array $valign
	 * @return Table
	 * 
	 * \
	 * Example:
	 * ```php
	 * $valign = [
	 *     'Column Heading' => 'baseline',
	 *     'Column Heading' => 'sub',
	 *     'Column Heading' => 'super',
	 *     'Column Heading' => 'text-top',
	 *     'Column Heading' => 'text-bottom',
	 *     'Column Heading' => 'middle',
	 *     'Column Heading' => 'top',
	 *     'Column Heading' => 'bottom',
	 * ];
	 * ```
	 */
	public function valign(array $valign): self {
		$this->valign = $valign;
		return $this;
	}

	/**
	 * Sets width for columns.
	 *
	 * @param string|array $column
	 * @param string|int $width
	 * @return Table
	 * 
	 * \
	 * Examples:
	 * ```php
	 * ('column', 150)
	 * 
	 * ('column', '50%')
	 * 
	 * ([
	 * 		'column1' => 150,
	 * 		'column2' => '50%',
	 * ]);
	 * ```
	 */
	public function width(string|array $column, string|int $width = ""): self {
		if(is_array($column)) {
			$this->width = $column;
		} else {
			$this->width[$column] = (is_numeric($width) ? $width."px" : $width);
		}
		return $this;
	}

	/**
	 * Provides a middleware to format values of a column while rendering the table.
	 *
	 * @param string|array $columns
	 * @param \Closure $callback
	 * @return Table
	 * 
	 * \
	 * Example:
	 * ```php
	 * $columns = [
	 *     'Column Heading',
	 *     'Column Heading 2',
	 * ];
	 * $callback = function($value) {
	 *     return ($value * 2);
	 * }
	 * ```
	 */
	public function formatColumn(string|array $columns, \Closure $callback): self {
		if(!empty($columns) && is_callable($callback)) {
			if(is_array($columns)) {
				foreach($columns as $column) {
					$this->formatings[$column] = $callback;
				}
			} else {
				$this->formatings[$columns] = $callback;
			}
		}
		return $this;
	}

	/**
	 * Renders the html of the table.
	 *
	 * @return string HTML
	 */
	public function render(): string {
		if(empty($this->data) || empty($this->data[0])) return false;

		$headings = array_keys($this->data[0]);
		$footingHeadingEqual = false;
		if($this->footer && !is_array($this->footer)) {
			$footingHeadingEqual = true;
			$footings = $headings;
		} else if(!empty($this->footer)) {
			$footings = $this->footer;
		}

		$dataData = [];
		foreach($this->data as $key => $dataRow) {
			foreach($dataRow as $dataKey => $dataValue) {
				if(strpos($dataValue, '<')===false) {
					$dataData[$key][$dataKey] = $dataValue;
				}
			}
		}

		$html = "<table role='table' cellspacing='0'" . (!$this->layoutFixed ? " data-layout-auto" : "") . (!$this->headerCapitalize ? " data-header-no-capitalize" : "") . ($this->headerAside ? " data-header-aside" : "") . " data-data='" . json_encode($dataData) . "'>
			" . (!empty($this->title) ? "<caption role='caption'>" . $this->title . "</caption>" : "");

			if(!$this->headerAside) {
				$html .= "<thead role='rowgroup'>
					<tr role='row'>";
					foreach($headings as $heading) {
						$classes = [];
						if(!empty($this->sortColumn) && $this->sortColumn == $heading && !in_array($heading, $this->sortDisable)) $classes[] = "sorted" . ($this->sortDesc ? " sorted-desc" : "");
						if(!empty($this->align[$heading])) $classes[] = "align-" . $this->align[$heading];
						if(!empty($this->valign[$heading])) $classes[] = "valign-" . $this->valign[$heading];

						$html .= "<th role='columnheader'"
							.(!empty($this->colspan[$heading]) ? " colspan='" . $this->colspan[$heading] . "'" : "")
							.(!empty($this->rowspan[$heading]) ? " rowspan='" . $this->rowspan[$heading] . "'" : "")
							.(!empty($this->width[$heading]) ? " width='" . $this->width[$heading] . "'" : "")
							.(!empty($classes) ? " class='" . implode(" ", $classes) . "'" : "")
							.(!empty($heading) && !in_array($heading, $this->sortDisable) ? " data-sort='" . $heading . "'" : "")
						.">"
							.(!empty($this->aliases[$heading]) ? $this->aliases[$heading] : $heading)
						."</th>";
					}
					$html .= "</tr>
				</thead>";
			}
			$html .= "<tbody role='rowgroup'>";
			foreach($this->data as $rowIndex => $row) {
				$html .= "<tr role='row'" . ($this->headerAside && $rowIndex>0 ? "class='newRow'" : "") . ">";
				$headingIndex = 0;
				$rowCount = count($row);
				foreach($row as $heading => $value) {
					$classes = [];
					if(!empty($this->align[$heading])) $classes[] = "align-" . $this->align[$heading];
					if(!empty($this->valign[$heading])) $classes[] = "valign-" . $this->valign[$heading];
					$classesHeader = [];
					if(!empty($this->align[$heading]) && !$this->headerAside) $classesHeader[] = "align-" . $this->align[$heading];
					if(!empty($this->valign[$heading])) $classesHeader[] = "valign-" . $this->valign[$heading];

					if(!empty($this->formatings[$heading])) $value = $this->formatings[$heading]($value);

					if($this->headerAside) $html .= "<th role='rowheading'"
						.(!empty($this->colspan[$heading]) ? " colspan='" . $this->colspan[$heading] . "'" : "")
						.(!empty($this->rowspan[$heading]) ? " rowspan='" . $this->rowspan[$heading] . "'" : "")
						.(!empty($classesHeader) ? " class='" . implode(" ", $classesHeader) . "'" : "")
						.(!empty($heading) && !in_array($heading, $this->sortDisable) ? " data-sort='" . $heading . "'" : "")
					.">"
						.(!empty($this->aliases[$heading]) ? $this->aliases[$heading] : $heading)
					."</th>";

					$html .= "<td role='cell'"
						.(!empty($this->colspan[$heading]) ? " colspan='" . $this->colspan[$heading] . "'" : "")
						.(!empty($this->rowspan[$heading]) ? " rowspan='" . $this->rowspan[$heading] . "'" : "")
						.(!empty($this->width[$heading]) ? " width='" . $this->width[$heading] . "'" : "")
						.(!empty($classes) ? " class='" . implode(" ", $classes) . "'" : "")
						." data-cell='".(!empty($this->aliases[$heading]) && $this->headerAside ? $this->aliases[$heading] : $heading)."'"
					.">"
						.$value
					."</td>";

					if($this->headerAside && $headingIndex<$rowCount-1) $html .= "</tr>";
					if($this->headerAside && $headingIndex<$rowCount-1) $html .= "<tr role='row'>";

					$headingIndex++;
				}
				$html .= "</tr>";
			}
			$html .= "</tbody>";
			if(!empty($footings) && (!$footingHeadingEqual || ($footingHeadingEqual && !$this->headerAside))) {
				$html .= "<tfoot role='rowgroup'" . ($footingHeadingEqual ? " class='equals-heading'" : "") . ">
				<tr role='row'>";
				foreach($footings as $footingKey => $footing) {
					$classes = [];
					if(!empty($this->align[$footingKey])) $classes[] = "align-" . $this->align[$footingKey];
					if(!empty($this->valign[$footingKey])) $classes[] = "valign-" . $this->valign[$footingKey];

					if(!empty($this->formatings[$footingKey]) && is_callable($this->formatings[$footingKey])) $footing = $this->formatings[$footingKey]($footing);

					$html .= "<th role='cell'"
						.(!empty($this->aliases[$footingKey]) ? " data-cell='" . $this->aliases[$footingKey] . "'" : "")
						.(!empty($this->colspan[$footingKey]) ? " colspan='" . $this->colspan[$footingKey] . "'" : "")
						.(!empty($this->width[$footingKey]) ? " width='" . $this->width[$footingKey] . "'" : "")
						.(!empty($this->rowspan[$footingKey]) ? " rowspan='" . $this->rowspan[$footingKey] . "'" : "")
						.(!empty($classes) ? " class='" . implode(" ", $classes) . "'" : "")
					.">"
						.$footing
					."</th>";
				}
				$html .= "</tr>
				</tfoot>";
			}

		$html .= "</table>";

		return $html;
	}

	private function _sort(string $column, bool $desc = false) {
		if(!in_array($column, $this->sortDisable)) {
			$sort = array_column($this->data, $column);
			array_multisort($sort, ($desc ? SORT_DESC : SORT_ASC), $this->data);
		}
	}
}
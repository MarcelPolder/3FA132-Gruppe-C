package dev.hv.db.model;

import java.beans.ConstructorProperties;
import java.util.Date;

import org.jdbi.v3.core.mapper.Nested;
import org.jdbi.v3.core.mapper.reflect.ColumnName;

import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

@Getter
@Setter
@NoArgsConstructor
public class DReading implements IDReading {

	// Region Private Fields

	@ColumnName("r_id")
	private Long Id;

	@ColumnName("r_comment")
	private String Comment;

	@ColumnName("r_date_of_reading")
	private Long DateOfReading;

	@Nested
	private IDCustomer Customer;

	@ColumnName("r_kind_of_meter")
	private String KindOfMeter;

	@ColumnName("r_meter_count")
	private Double MeterCount;

	@ColumnName("r_meter_id")
	private String MeterId;

	@ColumnName("r_substitute")
	private Boolean Substitute;

	@ConstructorProperties({ "r_id", "r_comment", "r_date_of_reading", "c_id", "c_firstname", "c_lastname",
			"r_kind_of_meter", "r_meter_count", "r_meter_id", "r_substitute" })
	public DReading(final Long id, final String _comment, final Long _dateofread, final Long c_id,
			final String c_firstname, final String c_lastname, final String _kindofMeter, final Double _meterCount,
			final String _meterId, final Boolean _substitute) {

		Id = id;
		Comment = _comment;
		DateOfReading = _dateofread;
		KindOfMeter = _kindofMeter;
		MeterCount = _meterCount;
		MeterId = _meterId;
		Substitute = _substitute;
		
		if (c_id != null) {
			Customer = new DCustomer(c_id, c_firstname, c_lastname);
		}
	}

	@Override
	public String printDateofreading() {
		// ToDo: add logic of StringConversion based on input
		return "";
	}

}

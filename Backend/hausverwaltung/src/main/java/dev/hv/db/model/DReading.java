package dev.hv.db.model;

import java.beans.ConstructorProperties;
import java.util.Date;

import javax.annotation.Nullable;

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

	@ColumnName("id")
	private int Id;

	@ColumnName("comment")
	private String Comment;

	@ColumnName("date_of_reading")
	private Long DateOfReading;

	@Nested @Nullable
	private IDCustomer Customer;

	@ColumnName("kind_of_meter")
	private String KindOfMeter;

	@ColumnName("meter_count")
	private Double MeterCount;

	@ColumnName("meter_id")
	private String MeterId;

	@ColumnName("substitute")
	private Boolean Substitute;

	@ConstructorProperties({ "rid", "comment", "date_of_reading", "cid", "firstname", "lastname",
			"kind_of_meter", "meter_count", "meter_id", "substitute" })
	public DReading(final int id, final String _comment, final Long _dateofread, final int c_id,
			final String c_firstname, final String c_lastname, final String _kindofMeter, final Double _meterCount,
			final String _meterId, final Boolean _substitute) {

		Id = id;
		Comment = _comment;
		DateOfReading = _dateofread;
		KindOfMeter = _kindofMeter;
		MeterCount = _meterCount;
		MeterId = _meterId;
		Substitute = _substitute;
		
		if (c_id != 0) {
			Customer = new DCustomer(c_id, c_firstname, c_lastname);
		}
	}

	@Override
	public String printDateofreading() {
		// ToDo: add logic of StringConversion based on input
		return "";
	}

}

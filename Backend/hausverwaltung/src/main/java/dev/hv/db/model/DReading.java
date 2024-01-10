package dev.hv.db.model;

import java.beans.ConstructorProperties;
import java.util.Date;

import javax.annotation.Nullable;

import org.jdbi.v3.core.mapper.Nested;
import org.jdbi.v3.core.mapper.reflect.ColumnName;

import com.fasterxml.jackson.annotation.JsonIdentityInfo;

import dev.hv.rest.model.IRReading;
import dev.hv.rest.model.RCustomer;
import lombok.Data;
import lombok.Generated;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

@Getter
@Setter
@NoArgsConstructor
@Data
@Generated
public class DReading implements IDReading {

	// Region Private Fields

	@ColumnName("id")
	private int Id;

	@ColumnName("comment")
	private String Comment;

	@ColumnName("date_of_reading")
	private String DateOfReading;

	@ColumnName("cid")
	private int Cid;

	@Nullable
	public IDCustomer Customer;

	@ColumnName("kind_of_meter")
	private String KindOfMeter;

	@ColumnName("meter_count")
	private int MeterCount;

	@ColumnName("meter_id")
	private String MeterId;

	@ColumnName("substitute")
	private int Substitute;

	@ConstructorProperties({ "id", "comment", "cid", "date_of_reading", "kind_of_meter", "meter_count", "meter_id",
			"substitute" })
	public DReading(final int id, final String _comment, final int cid, final String _dateofread,
			final String _kindofMeter, final int _meterCount, final String _meterId, final int _substitute) {

		Id = id;
		Comment = _comment;
		DateOfReading = _dateofread;
		KindOfMeter = _kindofMeter;
		MeterCount = _meterCount;
		MeterId = _meterId;
		Substitute = _substitute;
		Cid = cid;

		if (cid != 0) {
			Customer = new DCustomer(cid, "", "");
		}
	}

	@Override
	public String printDateofreading() {
		// ToDo: add logic of StringConversion based on input
		return "";
	}

	DReading(final int id, final String _comment, final String _dateofread, final String _kindofMeter,
			final int _meterCount, final String _meterId, final int _substitute, IDCustomer customer) {
		Id = id;
		Comment = _comment;
		DateOfReading = _dateofread;
		KindOfMeter = _kindofMeter;
		MeterCount = _meterCount;
		MeterId = _meterId;
		Substitute = _substitute;

		if (customer != null) {
			Cid = customer.getId();
			Customer = customer;
		}
	}

	public DReading(IRReading user) {
		Comment = user.getComment();
		Customer = new DCustomer(user.getCustomer());
		DateOfReading = user.getDateofreading();
		Id = user.getId();
		KindOfMeter = user.getKindofmeter();
		MeterCount = user.getMetercount();
		MeterId = user.getMeterid();
		Substitute = user.getSubstitute();
		Cid = user.getCustomer().getId();
	}

}

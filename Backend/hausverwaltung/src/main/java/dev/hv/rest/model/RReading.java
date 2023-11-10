package dev.hv.rest.model;

import com.fasterxml.jackson.annotation.JsonProperty;
import com.fasterxml.jackson.annotation.JsonTypeInfo;
import com.fasterxml.jackson.annotation.JsonTypeName;

import dev.hv.db.model.DCustomer;
import dev.hv.db.model.DReading;
import lombok.AllArgsConstructor;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import lombok.ToString;

@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@ToString
@JsonTypeInfo(include = JsonTypeInfo.As.WRAPPER_OBJECT, use = JsonTypeInfo.Id.NAME)
@JsonTypeName(value = "reading")
public class RReading implements IRReading {

	@JsonProperty(value = "comment")
	String Comment;
	
	@JsonProperty (value = "customer_id")
	RCustomer Customer;

	@JsonProperty(value = "date_of_reading")
	String Dateofreading;

	@JsonProperty(value = "id")
	Integer Id;

	@JsonProperty(value = "kind_of_meter")
	String Kindofmeter;

	@JsonProperty(value = "meter_count")
	Integer Metercount;

	@JsonProperty(value = "meter_id")
	String Meterid;

	@JsonProperty(value = "substitute")
	Integer Substitute;
	
	@Override
	public String printDateofreading() {
		return null;
	}

	public RReading(DReading user) {
		Comment = user.getComment();
		Customer = new RCustomer((DCustomer) user.getCustomer());
		Dateofreading = user.getDateOfReading();
		Id = user.getId();
		Kindofmeter = user.getKindOfMeter();
		Metercount = user.getMeterCount();
		Meterid = user.getMeterId();
		Substitute = user.getSubstitute();
	}

}

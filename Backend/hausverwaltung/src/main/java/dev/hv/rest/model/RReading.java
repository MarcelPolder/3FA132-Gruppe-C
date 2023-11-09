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

	@JsonProperty
	String Comment;
	
	@JsonProperty
	IRCustomer Customer;

	@JsonProperty
	Long Dateofreading;

	@JsonProperty
	Integer Id;

	@JsonProperty
	String Kindofmeter;

	@JsonProperty
	Double Metercount;

	@JsonProperty
	String Meterid;

	@JsonProperty
	Boolean Substitute;
	
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

package dev.hv.rest.model;

import java.util.List;

import com.fasterxml.jackson.annotation.JsonCreator;
import com.fasterxml.jackson.annotation.JsonProperty;
import com.fasterxml.jackson.annotation.JsonTypeInfo;
import com.fasterxml.jackson.annotation.JsonTypeName;

import dev.hv.db.model.DCustomer;
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
@JsonTypeName(value = "customer")
public class RCustomer implements IRCustomer {
	
	@JsonCreator
	public RCustomer(Integer _id){
		Id = _id;
	}

	@JsonProperty(value = "id")
	private Integer Id;

	@JsonProperty(value = "firstname")
	private String Firstname;

	@JsonProperty(value = "lastname")
	private String Lastname;
	
	public RCustomer(DCustomer customer) {
		Id = customer.getId();
		Firstname = customer.getFirstname();
		Lastname = customer.getLastname();
	}
}

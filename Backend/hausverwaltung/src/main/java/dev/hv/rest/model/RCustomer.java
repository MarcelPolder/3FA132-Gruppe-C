package dev.hv.rest.model;

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

	@JsonProperty
	private Integer Id;

	@JsonProperty
	private String Firstname;

	@JsonProperty
	private String Lastname;

	public RCustomer(DCustomer customer) {
		Id = customer.getId();
		Firstname = customer.getFirstname();
		Lastname = customer.getLastname();
	}
}

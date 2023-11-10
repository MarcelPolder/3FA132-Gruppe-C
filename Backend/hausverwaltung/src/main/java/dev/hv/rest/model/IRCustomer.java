package dev.hv.rest.model;

import com.fasterxml.jackson.annotation.JsonSubTypes;
import com.fasterxml.jackson.annotation.JsonSubTypes.Type;
import com.fasterxml.jackson.annotation.JsonTypeInfo;
import com.fasterxml.jackson.annotation.JsonTypeInfo.Id;

@JsonTypeInfo(defaultImpl = RCustomer.class, use = Id.DEDUCTION)
@JsonSubTypes(value = { @Type(value = RCustomer.class, name = "RCustomer") })
public interface IRCustomer {

	String getFirstname();

	Integer getId();

	String getLastname();

	void setFirstname(String firstName);

	void setId(Integer id);

	void setLastname(String lastName);
}

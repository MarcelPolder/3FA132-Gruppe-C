package dev.hv.db.model;

import java.beans.ConstructorProperties;

import org.jdbi.v3.core.mapper.reflect.ColumnName;

import dev.hv.rest.model.IRCustomer;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

@Getter
@Setter
@NoArgsConstructor
public class DCustomer implements IDCustomer {

	// Region Private Fields

	@ColumnName("id")
	private int Id;

	@ColumnName("firstname")
	private String Firstname;

	@ColumnName("lastname")
	private String Lastname;

	@ConstructorProperties({ "id", "firstname", "lastname" })
	public DCustomer(final int _id, final String _firstname, final String _lastname) {
		Id = _id;
		Firstname = _firstname;
		Lastname = _lastname;
	}

	public DCustomer(final String _firstname, final String _lastname) {
		Firstname = _firstname;
		Lastname = _lastname;
	}

	public DCustomer(IRCustomer customer) {
		Id = customer.getId();
		Firstname = customer.getFirstname();
		Lastname = customer.getLastname();
	}

}

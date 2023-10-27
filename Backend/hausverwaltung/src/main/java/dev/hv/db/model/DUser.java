package dev.hv.db.model;

import java.beans.ConstructorProperties;

import org.jdbi.v3.core.mapper.reflect.ColumnName;

import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

@Getter
@Setter
@NoArgsConstructor
public class DUser implements IDUser {

	// Region Private Fields
	
	@ColumnName("u_id")
	private Long Id;

	@ColumnName("u_firstname")
	private String Firstname;
	
	@ColumnName("u_lastname")
	private String Lastname;
	
	@ColumnName("u_password")
	private String Password;
	
	@ColumnName("u_token")
	private String Token;
	
	
	@ConstructorProperties({ "u_id", "u_firstname", "u_lastname", "u_password" , "u_token"})
	public DUser(final Long _id, final String _firstname, final String _lastname, final String _password, final String _token) {
		Id = _id;
		Firstname = _firstname;
		Lastname = _lastname;
		Password = _password;
		Token = _token;
	}
	
	public DUser (final String _firstname, final String _lastname, final String _password, final String _token) {
		Firstname = _firstname;
		Lastname = _lastname;
		Password = _password;
		Token = _token;
	}
	
	
}

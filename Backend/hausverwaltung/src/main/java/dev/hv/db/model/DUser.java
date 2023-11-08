package dev.hv.db.model;

import java.beans.ConstructorProperties;

import org.jdbi.v3.core.mapper.reflect.ColumnName;

import dev.hv.rest.model.IRUser;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

@Getter
@Setter
@NoArgsConstructor
public class DUser implements IDUser {

	// Region Private Fields
	
	@ColumnName("id")
	private int Id;

	@ColumnName("firstname")
	private String Firstname;
	
	@ColumnName("lastname")
	private String Lastname;
	
	@ColumnName("password")
	private String Password;
	
	@ColumnName("token")
	private String Token;
	
	
	@ConstructorProperties({ "id", "firstname", "lastname", "password" , "token"})
	public DUser(final int _id, final String _firstname, final String _lastname, final String _password, final String _token) {
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
	
	public DUser(IRUser user) {
		Id = user.getId();
		Firstname = user.getFirstname();
		Lastname = user.getLastname();
		Password = user.getPassword();
		Token = user.getToken();
	}
	
	
}

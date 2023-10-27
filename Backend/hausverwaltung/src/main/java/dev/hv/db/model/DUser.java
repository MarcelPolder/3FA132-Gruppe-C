package dev.hv.db.model;

public class DUser implements IDUser {

	// Region Private Fields
	
	private String Firstname;
	private String Lastname;
	private Long Id;
	private String Password;
	private String Token;
	

	// Region Getter
	
	@Override
	public String getFirstname() {
		return Firstname;
	}
	
	@Override
	public String getLastname() {
		return Lastname;
	}

	@Override
	public Long getId() {
		return Id;
	}

	@Override
	public String getPassword() {
		return Password;
	}

	@Override
	public String getToken() {
		return Token;
	}

	// Region Setter
	
	@Override
	public void setFirstname(String firstName) {
		Firstname = firstName;
	}
	
	@Override
	public void setLastname(String lastName) {
		Lastname = lastName;
	}

	@Override
	public void setId(Long id) {
		Id = id;
	}

	@Override
	public void setPassword(String password) {
		Password = password;
	}

	@Override
	public void setToken(String token) {
		Token = token;
	}

	
	
}

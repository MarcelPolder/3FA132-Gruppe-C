package dev.hv.db.model;

public class DCustomer implements IDCustomer {

	// Region Private Fields

	private String Firstname;
	private String Lastname;
	private Long Id;
	
	
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

	// Region Setter
	
	@Override
	public void setFirstname(String firstName) {
		Firstname = firstName;
	}

	@Override
	public void setId(Long id) {
		Id = id;
	}

	@Override
	public void setLastname(String lastName) {
		Lastname = lastName;
	}

}

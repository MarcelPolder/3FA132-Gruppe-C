package dev.hv.db.model;

public class DReading implements IDReading {
	
	// Region Private Fields
	
	private String Comment;
	private long DateOfReading;
	private long Id;
	private IDCustomer Customer;
	private String KindOfMeter;
	private double MeterCount;
	private String MeterId;
	private boolean Substitute;	


	// Region Getter
	
	@Override
	public String getComment() {
		return Comment;
	}

	@Override
	public IDCustomer getCustomer() {
		return Customer;
	}

	@Override
	public Long getDateofreading() {
		return DateOfReading;
	}

	@Override
	public Long getId() {
		return Id;
	}

	@Override
	public String getKindofmeter() {
		return KindOfMeter;
	}

	@Override
	public Double getMetercount() {
		return MeterCount;
	}

	@Override
	public String getMeterid() {
		return MeterId;
	}

	@Override
	public Boolean getSubstitute() {
		return Substitute;
	}

	@Override
	public String printDateofreading() {
		// ToDo: add logic of StringConversion based on input
		return "" ;
	}

	
	// Region Setter
	
	@Override
	public void setComment(String comment) {
		Comment = comment;
	}

	@Override
	public void setCustomer(IDCustomer customer) {
		Customer = customer;
	}

	@Override
	public void setDateofreading(Long dateOfReading) {
		DateOfReading = dateOfReading;
	}

	@Override
	public void setId(Long id) {
		Id = id;
	}

	@Override
	public void setKindofmeter(String kindOfMeter) {
		KindOfMeter = kindOfMeter;
	}

	@Override
	public void setMetercount(Double meterCount) {
		MeterCount = meterCount;
	}

	@Override
	public void setMeterid(String meterId) {
		MeterId = meterId;
	}

	@Override
	public void setSubstitute(Boolean substitute) {
		Substitute = substitute;
	}
	

}

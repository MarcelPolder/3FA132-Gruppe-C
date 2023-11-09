package dev.hv.rest.model;

import com.fasterxml.jackson.annotation.JsonProperty;
import com.fasterxml.jackson.annotation.JsonRootName;
import com.fasterxml.jackson.annotation.JsonTypeInfo;
import com.fasterxml.jackson.annotation.JsonTypeName;

import dev.hv.db.model.IDUser;
import jakarta.xml.bind.annotation.XmlAccessType;
import jakarta.xml.bind.annotation.XmlAccessorType;
import jakarta.xml.bind.annotation.XmlAttribute;
import jakarta.xml.bind.annotation.XmlRootElement;
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
@JsonTypeName(value = "user")
@XmlRootElement(name = "user")
@XmlAccessorType(XmlAccessType.FIELD)
public class RUser implements IRUser {

	@JsonProperty
	@XmlAttribute
	int id;
	
	@JsonProperty
	@XmlAttribute
	String firstname;

	@JsonProperty
	@XmlAttribute
	String lastname;

	@JsonProperty
	@XmlAttribute
	String password;

	@JsonProperty
	@XmlAttribute
	String token;
	
	public RUser(IDUser dbUser) {
		setId(dbUser.getId());
		setFirstname(dbUser.getFirstname());
		setLastname(dbUser.getLastname());
		setPassword(dbUser.getPassword());
		setToken(dbUser.getToken());
	}
}
